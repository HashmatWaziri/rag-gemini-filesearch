<?php

declare(strict_types=1);

use App\Enums\Glc\TutorViolationCategory;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorViolation;
use App\Models\User;
use App\Notifications\Glc\PersistentDirectAnswerSeekingNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\Fixtures\Glc\GeminiFake;
use Tests\Fixtures\Glc\TutorScenario;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
    config(['gemini.api_key' => 'test-key']);
});

it('logs each violation category distinguishably with student, timestamp, and excerpt', function (TutorViolationCategory $category): void {
    ['student' => $student] = TutorScenario::assignedStudent();

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(
            GeminiFake::chat('Let me redirect you back to your lesson.', $category->value),
        ),
    ]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), [
        'message' => 'A message that should trip the guardrail',
    ]);

    $violation = TutorViolation::query()->sole();

    expect($violation->category)->toBe($category)
        ->and($violation->user_id)->toBe($student->id)
        ->and($violation->tutor_conversation_id)->toBe($conversation->id)
        ->and($violation->excerpt)->toBe('A message that should trip the guardrail')
        ->and($violation->occurred_at->toDateTimeString())->toBe(now()->toDateTimeString());

    expect($conversation->messages()->where('role', 'assistant')->sole()->content)
        ->toBe('Let me redirect you back to your lesson.');
})->with(TutorViolationCategory::cases());

it('notifies linked teachers once the direct-answer threshold is reached in the window', function (): void {
    Notification::fake();

    ['student' => $student, 'teacher' => $teacher] = TutorScenario::assignedStudent();

    TutorViolation::factory()->count(2)->create([
        'user_id' => $student->id,
        'category' => TutorViolationCategory::DirectAnswerSeeking,
        'occurred_at' => now()->subDays(2),
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(
            GeminiFake::chat('I can guide you, but not give the answer.', TutorViolationCategory::DirectAnswerSeeking->value),
        ),
    ]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), [
        'message' => 'Just give me the homework answer',
    ]);

    Notification::assertSentToTimes($teacher, PersistentDirectAnswerSeekingNotification::class, 1);
    Notification::assertSentTo(
        $teacher,
        PersistentDirectAnswerSeekingNotification::class,
        function (PersistentDirectAnswerSeekingNotification $notification) use ($teacher, $student): bool {
            $data = $notification->toDatabase($teacher);

            return $data['student_id'] === $student->id && $data['violation_count'] === 3;
        },
    );
});

it('does not notify below the threshold', function (): void {
    Notification::fake();

    ['student' => $student] = TutorScenario::assignedStudent();

    TutorViolation::factory()->create([
        'user_id' => $student->id,
        'category' => TutorViolationCategory::DirectAnswerSeeking,
        'occurred_at' => now()->subDay(),
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(
            GeminiFake::chat('Here is a hint instead.', TutorViolationCategory::DirectAnswerSeeking->value),
        ),
    ]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Answer please']);

    Notification::assertNothingSent();
});

it('ignores direct-answer violations outside the window', function (): void {
    Notification::fake();

    ['student' => $student] = TutorScenario::assignedStudent();

    TutorViolation::factory()->count(2)->create([
        'user_id' => $student->id,
        'category' => TutorViolationCategory::DirectAnswerSeeking,
        'occurred_at' => now()->subDays(8),
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(
            GeminiFake::chat('Here is a hint instead.', TutorViolationCategory::DirectAnswerSeeking->value),
        ),
    ]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Answer please']);

    Notification::assertNothingSent();
});

it('does not notify for non direct-answer categories even at volume', function (): void {
    Notification::fake();

    ['student' => $student] = TutorScenario::assignedStudent();

    TutorViolation::factory()->count(3)->create([
        'user_id' => $student->id,
        'category' => TutorViolationCategory::OffTopic,
        'occurred_at' => now()->subDay(),
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(
            GeminiFake::chat('Back to the lesson please.', TutorViolationCategory::OffTopic->value),
        ),
    ]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'What football team do you like?']);

    Notification::assertNothingSent();
});

it('throttles to one database notification per teacher per student per window', function (): void {
    ['student' => $student, 'teacher' => $teacher] = TutorScenario::assignedStudent();

    TutorViolation::factory()->count(2)->create([
        'user_id' => $student->id,
        'category' => TutorViolationCategory::DirectAnswerSeeking,
        'occurred_at' => now()->subDays(2),
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(
            GeminiFake::chat('Guidance only, no answers.', TutorViolationCategory::DirectAnswerSeeking->value),
        ),
    ]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Give me the answer']);
    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Come on, just the answer']);

    expect(DatabaseNotification::query()
        ->where('notifiable_id', $teacher->id)
        ->where('type', PersistentDirectAnswerSeekingNotification::class)
        ->count())->toBe(1);
});

it('notifies every linked teacher of the student', function (): void {
    Notification::fake();

    ['student' => $student, 'teacher' => $teacher] = TutorScenario::assignedStudent();
    $secondTeacher = User::factory()->teacher()->create();
    $secondTeacher->assignedStudents()->attach($student);

    TutorViolation::factory()->count(2)->create([
        'user_id' => $student->id,
        'category' => TutorViolationCategory::DirectAnswerSeeking,
        'occurred_at' => now()->subDay(),
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(
            GeminiFake::chat('Guidance only.', TutorViolationCategory::DirectAnswerSeeking->value),
        ),
    ]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'The answer please']);

    Notification::assertSentToTimes($teacher, PersistentDirectAnswerSeekingNotification::class, 1);
    Notification::assertSentToTimes($secondTeacher, PersistentDirectAnswerSeekingNotification::class, 1);
});
