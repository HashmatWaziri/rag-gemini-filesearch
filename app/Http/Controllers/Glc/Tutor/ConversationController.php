<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Tutor;

use App\Models\Glc\StudentAssignment;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\User;
use App\Services\Glc\Tutor\TutorChatService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ConversationController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private TutorChatService $chat,
    ) {}

    public function index(): Response
    {
        return Inertia::render('glc/tutor/index', [
            'assignment' => $this->assignmentPayload(),
            'materialsReady' => $this->materialsReady(),
            'conversations' => $this->conversationsPayload(),
        ]);
    }

    public function store(): RedirectResponse
    {
        if (! $this->user->studentAssignment instanceof StudentAssignment || ! $this->materialsReady()) {
            return redirect()->route('tutor.index');
        }

        $conversation = $this->user->tutorConversations()->create([
            'last_activity_at' => now(),
        ]);

        return redirect()->route('tutor.conversations.show', $conversation);
    }

    public function show(TutorConversation $conversation): Response|RedirectResponse
    {
        abort_unless($conversation->user_id === $this->user->id, 403);

        if (! $this->user->studentAssignment instanceof StudentAssignment) {
            return redirect()->route('tutor.index');
        }

        $messages = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(fn (TutorMessage $message): array => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'citations' => data_get($message->metadata, 'citations', []),
                'curriculum_sources' => data_get($message->metadata, 'curriculum_sources', []),
            ])
            ->all();

        return Inertia::render('glc/tutor/chat', [
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
            ],
            'messages' => $messages,
            'conversations' => $this->conversationsPayload(),
            'assignment' => $this->assignmentPayload(),
            'materialsReady' => $this->materialsReady(),
        ]);
    }

    private function materialsReady(): bool
    {
        $assignment = $this->user->studentAssignment;

        return $assignment instanceof StudentAssignment && $this->chat->hasPublishedMaterials($assignment);
    }

    /**
     * @return array{course: string, level: string, unit: string}|null
     */
    private function assignmentPayload(): ?array
    {
        $assignment = $this->user->studentAssignment;

        if (! $assignment instanceof StudentAssignment) {
            return null;
        }

        $assignment->loadMissing(['course', 'level', 'unit']);

        return [
            'course' => $assignment->course->name,
            'level' => $assignment->level->name,
            'unit' => $assignment->unit->name,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function conversationsPayload(): array
    {
        return $this->user->tutorConversations()
            ->withCount('messages')
            ->get()
            ->map(fn (TutorConversation $conversation): array => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'message_count' => $conversation->messages_count,
                'last_activity_at' => $conversation->last_activity_at?->toIso8601String(),
            ])
            ->all();
    }
}
