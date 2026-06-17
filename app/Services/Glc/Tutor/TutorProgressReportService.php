<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorProgressReport;
use App\Models\Glc\TutorUsageDaily;
use App\Models\Glc\TutorViolation;
use App\Models\Glc\WritingSubmission;
use App\Models\User;
use App\Services\Glc\Ai\GlcAiCostGuard;
use App\Services\Glc\Ai\PlacementAiSettings;
use Illuminate\Support\Carbon;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

final class TutorProgressReportService
{
    public const FAILURE_MESSAGE = 'We could not generate this progress report right now. Please try again later.';

    public function __construct(
        private readonly PlacementAiSettings $aiSettings,
        private readonly TutorWeakAreaAnalyzer $weakAreas,
        private readonly GlcAiCostGuard $costGuard,
    ) {}

    public function queue(User $staff, User $student, int $windowDays = 30): TutorProgressReport
    {
        $periodEnd = now()->toDateString();
        $periodStart = now()->subDays($windowDays)->toDateString();

        $recent = TutorProgressReport::query()
            ->where('user_id', $student->id)
            ->where('generated_by', $staff->id)
            ->where('created_at', '>=', now()->subHour())
            ->exists();

        abort_if($recent, 429, 'Please wait before generating another report for this student.');

        return TutorProgressReport::query()->create([
            'user_id' => $student->id,
            'generated_by' => $staff->id,
            'status' => 'pending',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
    }

    public function generate(TutorProgressReport $report): void
    {
        if (! $this->aiSettings->taskIsConfigured(PlacementAiSettings::TASK_TUTOR_PROGRESS)) {
            $this->markFailed($report);

            return;
        }

        $student = $report->student;
        $windowDays = (int) (Carbon::parse($report->period_start)->diffInDays(Carbon::parse($report->period_end)) ?: 30);

        try {
            $this->costGuard->assertWithinLimits();
            $this->aiSettings->hydrateProviderConfig();
            $selection = $this->aiSettings->selection(PlacementAiSettings::TASK_TUTOR_PROGRESS);

            $prompt = $this->buildPrompt($student, $report, $windowDays);

            $response = (new TutorProgressSummaryAgent)->prompt(
                $prompt,
                provider: $selection['provider'],
                model: $selection['model'],
            );

            if (! $response instanceof StructuredAgentResponse) {
                $this->markFailed($report);

                return;
            }

            $payload = [
                'summary' => is_string($response['summary'] ?? null) ? $response['summary'] : '',
                'strengths' => $this->stringList($response['strengths'] ?? []),
                'focus_areas' => $this->stringList($response['focus_areas'] ?? []),
                'engagement_note' => is_string($response['engagement_note'] ?? null) ? $response['engagement_note'] : '',
            ];

            if ($payload['summary'] === '') {
                $this->markFailed($report);

                return;
            }

            $report->update([
                'status' => 'completed',
                'payload' => $payload,
                'error' => null,
            ]);
        } catch (Throwable) {
            $this->markFailed($report);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function historyFor(User $student): array
    {
        return TutorProgressReport::query()
            ->where('user_id', $student->id)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (TutorProgressReport $report): array => [
                'id' => $report->id,
                'status' => $report->status,
                'period_start' => $report->period_start->toDateString(),
                'period_end' => $report->period_end->toDateString(),
                'payload' => $report->payload,
                'error' => $report->error,
                'created_at' => $report->created_at?->toIso8601String(),
            ])
            ->all();
    }

    private function buildPrompt(User $student, TutorProgressReport $report, int $windowDays): string
    {
        $since = Carbon::parse($report->period_start)->startOfDay();

        $conversationCount = TutorConversation::query()
            ->where('user_id', $student->id)
            ->where('last_activity_at', '>=', $since)
            ->count();

        $messageCount = TutorUsageDaily::query()
            ->where('user_id', $student->id)
            ->where('date', '>=', $report->period_start)
            ->sum('message_count');

        $activeMinutes = TutorUsageDaily::query()
            ->where('user_id', $student->id)
            ->where('date', '>=', $report->period_start)
            ->sum('active_minutes');

        $summaries = TutorConversation::query()
            ->where('user_id', $student->id)
            ->whereNotNull('summary')
            ->where('last_activity_at', '>=', $since)
            ->latest('last_activity_at')
            ->limit(5)
            ->pluck('summary')
            ->filter(fn (?string $summary): bool => is_string($summary) && $summary !== '')
            ->values()
            ->all();

        $writingCount = WritingSubmission::query()
            ->where('user_id', $student->id)
            ->where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->count();

        $violationCount = TutorViolation::query()
            ->where('user_id', $student->id)
            ->where('occurred_at', '>=', $since)
            ->count();

        $weakAreas = $this->weakAreas->forStudent($student, $windowDays);

        $student->loadMissing(['studentAssignment.course', 'studentAssignment.level', 'studentAssignment.unit']);
        $assignment = $student->studentAssignment;

        $assignmentLine = $assignment === null
            ? 'No course assignment on file.'
            : sprintf(
                'Assigned scope: %s / %s / %s',
                $assignment->course->name,
                $assignment->level->name,
                $assignment->unit->name,
            );

        $summaryBlock = $summaries === []
            ? 'No conversation summaries recorded in this period.'
            : implode("\n\n", array_map(fn (string $summary): string => '- '.$summary, $summaries));

        $writingBlock = collect($weakAreas['writing_dimensions'])
            ->map(fn (array $row): string => sprintf('%s average %.1f/5 (%d samples)', $row['label'], $row['average_score'], $row['sample_count']))
            ->implode("\n");

        if ($writingBlock === '') {
            $writingBlock = 'No completed writing submissions in this period.';
        }

        $violationBlock = collect($weakAreas['violation_categories'])
            ->map(fn (array $row): string => sprintf('%s: %d', $row['label'], $row['count']))
            ->implode("\n");

        if ($violationBlock === '') {
            $violationBlock = 'No guardrail violations in this period.';
        }

        return <<<PROMPT
Student: {$student->name}
{$assignmentLine}
Reporting period: {$report->period_start->toDateString()} to {$report->period_end->toDateString()}

Engagement:
- Conversations with activity: {$conversationCount}
- Student messages (rollup): {$messageCount}
- Approximate active minutes: {$activeMinutes}
- Completed writing submissions: {$writingCount}
- Guardrail violations logged: {$violationCount}

Conversation summaries:
{$summaryBlock}

Writing dimension averages:
{$writingBlock}

Violation breakdown:
{$violationBlock}
PROMPT;
    }

    private function markFailed(TutorProgressReport $report): void
    {
        $report->update([
            'status' => 'failed',
            'error' => self::FAILURE_MESSAGE,
        ]);
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            fn (mixed $item): bool => is_string($item) && $item !== '',
        ));
    }
}
