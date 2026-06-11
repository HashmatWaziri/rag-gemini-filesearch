<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Tutor;

use App\Enums\Glc\TutorViolationCategory;
use App\Enums\Glc\UserRole;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\Glc\TutorViolation;
use App\Models\Glc\WritingSubmission;
use App\Models\User;
use App\Services\Glc\Tutor\StaffTutorAccess;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StaffTutorController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private StaffTutorAccess $access,
    ) {}

    public function index(): Response
    {
        $canViewAll = $this->user->role instanceof UserRole && $this->user->role->canViewAllStudents();

        $students = ($canViewAll
            ? User::query()->where('role', UserRole::Student)
            : $this->user->assignedStudents())
            ->withCount('tutorConversations')
            ->withMax('tutorConversations', 'last_activity_at')
            ->orderBy('name')
            ->get()
            ->map(fn (User $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'conversation_count' => (int) $student->getAttribute('tutor_conversations_count'),
                'last_active_at' => $student->getAttribute('tutor_conversations_max_last_activity_at'),
            ])
            ->all();

        return Inertia::render('glc/tutor/staff/index', [
            'students' => $students,
        ]);
    }

    public function student(User $student): Response
    {
        $this->access->authorizeStudent($this->user, $student);

        $conversations = $student->tutorConversations()
            ->withCount('messages')
            ->get()
            ->map(fn (TutorConversation $conversation): array => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'message_count' => $conversation->getAttribute('messages_count'),
                'last_activity_at' => $conversation->last_activity_at?->toIso8601String(),
            ]);

        $submissions = $student->writingSubmissions()
            ->get()
            ->map(fn (WritingSubmission $submission): array => [
                'id' => $submission->id,
                'status' => $submission->status,
                'excerpt' => Str::limit($submission->text, 90),
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

        return Inertia::render('glc/tutor/staff/student', [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
            'activity' => [
                'conversation_count' => $conversations->count(),
                'last_active_at' => $student->tutorConversations()->max('last_activity_at'),
            ],
            'conversations' => $conversations->all(),
            'writingSubmissions' => $submissions->all(),
            'violations' => $violations->all(),
        ]);
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
