<?php

declare(strict_types=1);

namespace App\Jobs\Glc\Tutor;

use App\Models\Glc\TutorConversation;
use App\Services\Glc\Tutor\ConversationRotator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Middleware\WithoutOverlapping;

#[Timeout(120)]
final class RotateConversationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly TutorConversation $conversation) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping('glc-tutor-rotate-'.$this->conversation->id),
        ];
    }

    public function handle(ConversationRotator $rotator): void
    {
        $rotator->rotate($this->conversation);
    }
}
