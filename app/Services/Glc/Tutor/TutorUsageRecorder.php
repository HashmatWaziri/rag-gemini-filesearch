<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Models\Glc\TutorMessage;
use App\Models\Glc\TutorUsageDaily;
use Illuminate\Support\Carbon;

final class TutorUsageRecorder
{
    public function recordStudentMessage(TutorMessage $message): void
    {
        if (! $this->enabled()) {
            return;
        }

        if ($message->role !== 'user') {
            return;
        }

        $message->loadMissing('conversation');
        $studentId = $message->conversation->user_id;
        $date = ($message->created_at ?? now())->toDateString();

        $daily = TutorUsageDaily::query()
            ->where('user_id', $studentId)
            ->whereDate('date', $date)
            ->first();

        if (! $daily instanceof TutorUsageDaily) {
            $daily = TutorUsageDaily::query()->create([
                'user_id' => $studentId,
                'date' => $date,
                'active_minutes' => 0,
                'message_count' => 0,
                'conversation_starts' => 0,
            ]);
        }

        $previousMessage = TutorMessage::query()
            ->where('tutor_conversation_id', $message->tutor_conversation_id)
            ->where('role', 'user')
            ->where('id', '<', $message->id)
            ->latest('id')
            ->first();

        $incrementMinutes = 0;

        if ($previousMessage instanceof TutorMessage) {
            $gapSeconds = abs($message->created_at->diffInSeconds($previousMessage->created_at));
            $gapMinutes = (int) ceil($gapSeconds / 60);
            $maxGap = config()->integer('glc.tutor.usage_active_gap_minutes', 5);
            $incrementMinutes = min($gapMinutes, $maxGap);
        } else {
            $daily->conversation_starts++;
            $incrementMinutes = 1;
        }

        $cap = config()->integer('glc.tutor.usage_daily_active_minutes_cap', 120);
        $daily->active_minutes = min($cap, $daily->active_minutes + $incrementMinutes);
        $daily->message_count++;
        $daily->save();
    }

    public function activeMinutesForStudent(int $studentId, int $days): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        return (int) TutorUsageDaily::query()
            ->where('user_id', $studentId)
            ->where('date', '>=', Carbon::now()->subDays($days)->toDateString())
            ->sum('active_minutes');
    }

    private function enabled(): bool
    {
        return (bool) config('glc.tutor.progress_analytics_enabled', false);
    }
}
