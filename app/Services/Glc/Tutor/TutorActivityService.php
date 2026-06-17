<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Enums\Glc\UserRole;
use App\Models\Glc\StudentAssignment;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorViolation;
use App\Models\Glc\WritingSubmission;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class TutorActivityService
{
    public function __construct(
        private readonly TutorWeakAreaAnalyzer $weakAreas,
    ) {}

    /**
     * @return array{
     *     students: list<array<string, mixed>>,
     *     canViewAll: bool,
     *     progressAnalyticsEnabled: bool,
     *     cohortWeakAreas: array<string, mixed>|null,
     *     filters: array{sort: string, inactive_days: int|null}
     * }
     */
    public function rosterFor(User $staff, ?string $sort = null, ?int $inactiveDays = null): array
    {
        $canViewAll = $staff->role instanceof UserRole && $staff->role->canViewAllStudents();
        $attentionWindowDays = config()->integer('glc.tutor.activity_attention_window_days', 30);
        $attentionSince = now()->subDays($attentionWindowDays);

        $students = $this->studentQuery($staff, $canViewAll)
            ->with(['studentAssignment.course', 'studentAssignment.level', 'studentAssignment.unit'])
            ->withCount('tutorConversations')
            ->withCount(['writingSubmissions'])
            ->withMax('tutorConversations', 'last_activity_at')
            ->get();

        $violationCounts = TutorViolation::query()
            ->selectRaw('user_id, count(*) as aggregate')
            ->whereIn('user_id', $students->pluck('id'))
            ->where('occurred_at', '>=', $attentionSince)
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id');

        $students = $students
            ->map(function (User $student) use ($violationCounts): array {
                $student->setAttribute(
                    'recent_violations_count',
                    (int) ($violationCounts[$student->id] ?? 0),
                );

                return $this->rosterRow($student);
            })
            ->when(
                $inactiveDays !== null,
                fn (Collection $rows): Collection => $rows->filter(
                    fn (array $row): bool => $this->isInactive($row['last_active_at'], $inactiveDays),
                ),
            )
            ->values();

        $students = $this->sortRoster($students, $sort ?? 'last_active');

        $progressAnalyticsEnabled = $this->progressAnalyticsEnabled();

        return [
            'students' => $students->all(),
            'canViewAll' => $canViewAll,
            'progressAnalyticsEnabled' => $progressAnalyticsEnabled,
            'cohortWeakAreas' => $canViewAll && $progressAnalyticsEnabled
                ? $this->weakAreas->cohortSummary($students->pluck('id')->all(), $attentionWindowDays)
                : null,
            'filters' => [
                'sort' => $sort ?? 'last_active',
                'inactive_days' => $inactiveDays,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detailFor(User $staff, User $student): array
    {
        $student->loadMissing(['studentAssignment.course', 'studentAssignment.level', 'studentAssignment.unit']);

        $conversations = $student->tutorConversations()
            ->withCount('messages')
            ->orderByDesc('last_activity_at')
            ->get()
            ->map(fn (TutorConversation $conversation): array => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'message_count' => (int) $conversation->getAttribute('messages_count'),
                'last_activity_at' => $conversation->last_activity_at?->toIso8601String(),
                'summary' => $conversation->summary,
            ]);

        $submissions = $student->writingSubmissions()
            ->latest('id')
            ->get()
            ->map(fn (WritingSubmission $submission): array => [
                'id' => $submission->id,
                'status' => $submission->status,
                'excerpt' => \Illuminate\Support\Str::limit($submission->text, 90),
                'created_at' => $submission->created_at?->toIso8601String(),
            ]);

        $violations = TutorViolation::query()
            ->where('user_id', $student->id)
            ->latest('occurred_at')
            ->get()
            ->map(fn (TutorViolation $violation): array => [
                'id' => $violation->id,
                'category' => $violation->category->value,
                'category_label' => $violation->category->label(),
                'excerpt' => $violation->excerpt,
                'occurred_at' => $violation->occurred_at->toIso8601String(),
            ]);

        $latestSummaryConversation = $conversations->first(
            fn (array $conversation): bool => is_string($conversation['summary'] ?? null)
                && $conversation['summary'] !== '',
        );

        $progressAnalyticsEnabled = $this->progressAnalyticsEnabled();
        $attentionWindowDays = config()->integer('glc.tutor.activity_attention_window_days', 30);
        $recentViolationsCount = TutorViolation::query()
            ->where('user_id', $student->id)
            ->where('occurred_at', '>=', now()->subDays($attentionWindowDays))
            ->count();

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
            'assignment' => $this->assignmentPayload($student->studentAssignment),
            'activity' => [
                'conversation_count' => $conversations->count(),
                'last_active_at' => $student->tutorConversations()->max('last_activity_at'),
                'writing_submission_count' => $submissions->count(),
                'recent_violations_count' => $recentViolationsCount,
                'active_minutes_last_30_days' => $progressAnalyticsEnabled
                    ? app(TutorUsageRecorder::class)->activeMinutesForStudent($student->id, 30)
                    : null,
            ],
            'latestTopicsSummary' => $latestSummaryConversation['summary'] ?? null,
            'weakAreas' => $progressAnalyticsEnabled
                ? $this->weakAreas->forStudent($student, $attentionWindowDays)
                : null,
            'progressReports' => $progressAnalyticsEnabled
                ? app(TutorProgressReportService::class)->historyFor($student)
                : [],
            'conversations' => $conversations->all(),
            'writingSubmissions' => $submissions->all(),
            'violations' => $violations->all(),
            'progressAnalyticsEnabled' => $progressAnalyticsEnabled,
        ];
    }

    public function progressAnalyticsEnabled(): bool
    {
        return (bool) config('glc.tutor.progress_analytics_enabled', false);
    }

    /**
     * @return Builder<User>
     */
    private function studentQuery(User $staff, bool $canViewAll): Builder
    {
        if ($canViewAll) {
            return User::query()->where('role', UserRole::Student);
        }

        return $staff->assignedStudents()->getQuery();
    }

    /**
     * @return array<string, mixed>
     */
    private function rosterRow(User $student): array
    {
        $assignment = $student->studentAssignment;
        $lastActive = $student->getAttribute('tutor_conversations_max_last_activity_at');

        return [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'conversation_count' => (int) $student->getAttribute('tutor_conversations_count'),
            'writing_submission_count' => (int) $student->getAttribute('writing_submissions_count'),
            'recent_violations_count' => (int) $student->getAttribute('recent_violations_count'),
            'last_active_at' => $lastActive instanceof CarbonInterface
                ? $lastActive->toIso8601String()
                : (is_string($lastActive) ? $lastActive : null),
            'assignment' => $this->assignmentPayload($assignment),
            'needs_attention' => (int) $student->getAttribute('recent_violations_count') > 0,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function assignmentPayload(?StudentAssignment $assignment): ?array
    {
        if (! $assignment instanceof StudentAssignment) {
            return null;
        }

        $assignment->loadMissing(['course', 'level', 'unit']);

        return [
            'course_id' => $assignment->course_id,
            'course_level_id' => $assignment->course_level_id,
            'course_unit_id' => $assignment->course_unit_id,
            'course' => $assignment->course->name,
            'level' => $assignment->level->name,
            'unit' => $assignment->unit->name,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $students
     * @return Collection<int, array<string, mixed>>
     */
    private function sortRoster(Collection $students, string $sort): Collection
    {
        return match ($sort) {
            'violations' => $students->sortByDesc('recent_violations_count')->values(),
            'name' => $students->sortBy('name')->values(),
            default => $students->sortBy(function (array $row): int {
                if (! is_string($row['last_active_at'] ?? null)) {
                    return PHP_INT_MIN;
                }

                return strtotime($row['last_active_at']) ?: PHP_INT_MIN;
            })->values(),
        };
    }

    private function isInactive(?string $lastActiveAt, int $inactiveDays): bool
    {
        if ($lastActiveAt === null) {
            return true;
        }

        $lastActive = \Illuminate\Support\Carbon::parse($lastActiveAt);

        return $lastActive->lt(now()->subDays($inactiveDays));
    }
}
