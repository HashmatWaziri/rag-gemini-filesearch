<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Enums\Glc\TutorViolationCategory;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\Glc\TutorViolation;
use App\Models\User;
use App\Notifications\Glc\PersistentDirectAnswerSeekingNotification;
use Carbon\CarbonInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

final class TutorViolationRecorder
{
    public function record(
        TutorConversation $conversation,
        TutorMessage $studentMessage,
        TutorViolationCategory $category,
    ): TutorViolation {
        $violation = TutorViolation::query()->create([
            'user_id' => $conversation->user_id,
            'tutor_conversation_id' => $conversation->id,
            'tutor_message_id' => $studentMessage->id,
            'category' => $category,
            'excerpt' => Str::limit($studentMessage->content, 500),
            'occurred_at' => now(),
        ]);

        if ($category === TutorViolationCategory::DirectAnswerSeeking) {
            $this->notifyTeachersOfPersistentPattern($conversation->user);
        }

        return $violation;
    }

    private function notifyTeachersOfPersistentPattern(User $student): void
    {
        $threshold = config()->integer('glc.tutor.violation_notification_threshold', 3);
        $windowDays = config()->integer('glc.tutor.violation_notification_window_days', 7);
        $windowStart = now()->subDays($windowDays);

        $count = TutorViolation::query()
            ->where('user_id', $student->id)
            ->where('category', TutorViolationCategory::DirectAnswerSeeking)
            ->where('occurred_at', '>=', $windowStart)
            ->count();

        if ($count < $threshold) {
            return;
        }

        foreach ($student->teachers as $teacher) {
            if ($this->alreadyNotified($teacher, $student, $windowStart)) {
                continue;
            }

            $teacher->notify(new PersistentDirectAnswerSeekingNotification($student, $count, $windowDays));
        }
    }

    private function alreadyNotified(User $teacher, User $student, CarbonInterface $windowStart): bool
    {
        return $teacher->notifications()
            ->where('type', PersistentDirectAnswerSeekingNotification::class)
            ->where('created_at', '>=', $windowStart)
            ->get()
            ->contains(fn (DatabaseNotification $notification): bool => (int) data_get($notification->data, 'student_id') === $student->id);
    }
}
