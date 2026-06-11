<?php

declare(strict_types=1);

namespace App\Notifications\Glc;

use App\Models\User;
use Illuminate\Notifications\Notification;

final class PersistentDirectAnswerSeekingNotification extends Notification
{
    public function __construct(
        private readonly User $student,
        private readonly int $violationCount,
        private readonly int $windowDays,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'persistent_direct_answer_seeking',
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'violation_count' => $this->violationCount,
            'window_days' => $this->windowDays,
            'message' => sprintf(
                '%s has asked the AI Tutor for direct homework answers %d times in the last %d days.',
                $this->student->name,
                $this->violationCount,
                $this->windowDays,
            ),
        ];
    }
}
