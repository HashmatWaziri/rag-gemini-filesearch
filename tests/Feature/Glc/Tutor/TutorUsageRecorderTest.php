<?php

declare(strict_types=1);

use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\Glc\TutorUsageDaily;
use App\Models\User;
use App\Services\Glc\Tutor\TutorUsageRecorder;

it('counts active minutes from gaps between student messages', function (): void {
    config(['glc.tutor.progress_analytics_enabled' => true]);

    $student = User::factory()->student()->create();
    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);
    $recorder = app(TutorUsageRecorder::class);

    $first = TutorMessage::factory()->create([
        'tutor_conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Hello',
        'created_at' => now()->subMinutes(10),
    ]);

    $recorder->recordStudentMessage($first);

    $second = TutorMessage::factory()->create([
        'tutor_conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Follow up',
        'created_at' => now()->subMinutes(7),
    ]);

    $recorder->recordStudentMessage($second);

    $daily = TutorUsageDaily::query()->where('user_id', $student->id)->sole();

    expect($daily->message_count)->toBe(2)
        ->and($daily->active_minutes)->toBe(4)
        ->and($daily->conversation_starts)->toBe(1);
});

it('does not record usage when analytics are disabled', function (): void {
    config(['glc.tutor.progress_analytics_enabled' => false]);

    $conversation = TutorConversation::factory()->create();
    $message = TutorMessage::factory()->create([
        'tutor_conversation_id' => $conversation->id,
        'role' => 'user',
    ]);

    app(TutorUsageRecorder::class)->recordStudentMessage($message);

    expect(TutorUsageDaily::query()->count())->toBe(0);
});

it('caps daily active minutes', function (): void {
    config([
        'glc.tutor.progress_analytics_enabled' => true,
        'glc.tutor.usage_daily_active_minutes_cap' => 10,
        'glc.tutor.usage_active_gap_minutes' => 5,
    ]);

    $student = User::factory()->student()->create();
    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);
    $recorder = app(TutorUsageRecorder::class);

    TutorUsageDaily::factory()->create([
        'user_id' => $student->id,
        'date' => now()->toDateString(),
        'active_minutes' => 9,
        'message_count' => 5,
        'conversation_starts' => 1,
    ]);

    $previous = TutorMessage::factory()->create([
        'tutor_conversation_id' => $conversation->id,
        'role' => 'user',
        'created_at' => now()->subMinutes(10),
    ]);

    $recorder->recordStudentMessage($previous);

    $latest = TutorMessage::factory()->create([
        'tutor_conversation_id' => $conversation->id,
        'role' => 'user',
        'created_at' => now(),
    ]);

    $recorder->recordStudentMessage($latest);

    expect(TutorUsageDaily::query()->where('user_id', $student->id)->sole()->active_minutes)->toBe(10);
});
