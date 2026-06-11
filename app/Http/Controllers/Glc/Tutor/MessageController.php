<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Tutor;

use App\Models\Glc\TutorConversation;
use App\Models\User;
use App\Services\Glc\Tutor\TutorChatService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class MessageController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private TutorChatService $chat,
    ) {}

    public function store(Request $request, TutorConversation $conversation): RedirectResponse
    {
        abort_unless($conversation->user_id === $this->user->id, 403);

        /** @var array{message: string} $validated */
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $this->chat->respond($conversation, $validated['message']);

        return redirect()->route('tutor.conversations.show', $conversation);
    }
}
