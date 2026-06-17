<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Tutor;

use App\Enums\Glc\TutorViolationCategory;
use App\Jobs\Glc\Tutor\GenerateTutorProgressReportJob;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\Glc\WritingSubmission;
use App\Models\User;
use App\Services\Glc\Tutor\StaffTutorAccess;
use App\Services\Glc\Tutor\TutorActivityService;
use App\Services\Glc\Tutor\TutorProgressReportService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StaffTutorController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private StaffTutorAccess $access,
        private TutorActivityService $activity,
        private TutorProgressReportService $progressReports,
    ) {}

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'sort' => ['nullable', 'string', 'in:last_active,violations,name'],
            'inactive_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $payload = $this->activity->rosterFor(
            $this->user,
            $validated['sort'] ?? null,
            isset($validated['inactive_days']) ? (int) $validated['inactive_days'] : null,
        );

        return Inertia::render('glc/tutor/staff/index', $payload);
    }

    public function student(User $student): Response
    {
        $this->access->authorizeStudent($this->user, $student);

        return Inertia::render('glc/tutor/staff/student', $this->activity->detailFor($this->user, $student));
    }

    public function storeProgressReport(User $student): RedirectResponse
    {
        abort_unless($this->activity->progressAnalyticsEnabled(), 404);

        $this->access->authorizeStudent($this->user, $student);

        $report = $this->progressReports->queue($this->user, $student);
        GenerateTutorProgressReportJob::dispatch($report->id);

        return back()->with('status', 'Progress report is being prepared.');
    }

    public function conversation(TutorConversation $conversation): Response
    {
        $this->access->authorizeStudent($this->user, $conversation->user);

        $messages = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(fn (TutorMessage $message): array => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'rotated' => (bool) data_get($message->metadata, 'rotated', false),
                'violation' => $this->violationLabel($message),
                'created_at' => $message->created_at?->toIso8601String(),
            ])
            ->all();

        return Inertia::render('glc/tutor/staff/conversation', [
            'student' => [
                'id' => $conversation->user->id,
                'name' => $conversation->user->name,
            ],
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'summary' => $conversation->summary,
                'last_activity_at' => $conversation->last_activity_at?->toIso8601String(),
            ],
            'messages' => $messages,
        ]);
    }

    public function writing(WritingSubmission $submission): Response
    {
        $this->access->authorizeStudent($this->user, $submission->user);

        return Inertia::render('glc/tutor/staff/writing', [
            'student' => [
                'id' => $submission->user->id,
                'name' => $submission->user->name,
            ],
            'submission' => [
                'id' => $submission->id,
                'status' => $submission->status,
                'text' => $submission->text,
                'feedback' => $submission->feedback,
                'highlights' => $submission->highlights ?? [],
                'error' => $submission->error,
                'created_at' => $submission->created_at?->toIso8601String(),
            ],
        ]);
    }

    private function violationLabel(TutorMessage $message): ?string
    {
        $value = data_get($message->metadata, 'violation');

        return is_string($value) ? TutorViolationCategory::tryFrom($value)?->label() : null;
    }
}
