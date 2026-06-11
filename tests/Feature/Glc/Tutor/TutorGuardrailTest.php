<?php

declare(strict_types=1);

use App\Enums\Glc\TutorViolationCategory;
use App\Enums\SettingKey;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorViolation;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\Glc\PersistentDirectAnswerSeekingNotification;
use App\Services\Glc\Tutor\GlcTutorAgent;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Fixtures\Glc\TutorScenario;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
    config([
        'gemini.api_key' => 'test-key',
        'ai.providers.gemini.key' => 'test-key',
    ]);
});

function prepareTutorGuardrailScenario(): void
{
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');
}

it('logs each violation category distinguishably with student, timestamp, and excerpt', function (TutorViolationCategory $category): void {
    ['student' => $student] = TutorScenario::assignedStudent();
    prepareTutorGuardrailScenario();

    GlcTutorAgent::fake([[
        'reply' => 'Let me redirect you back to your lesson.',
        'violation' => $category->value,
    ]])->preventStrayPrompts();

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
    prepareTutorGuardrailScenario();

    TutorViolation::factory()->count(2)->create([
        'user_id' => $student->id,
        'category' => TutorViolationCategory::DirectAnswerSeeking,
        'occurred_at' => now()->subDays(2),
    ]);

    GlcTutorAgent::fake([[
        'reply' => 'I can guide you, but not give the answer.',
        'violation' => TutorViolationCategory::DirectAnswerSeeking->value,
    ]])->preventStrayPrompts();

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
    prepareTutorGuardrailScenario();

    TutorViolation::factory()->create([
        'user_id' => $student->id,
        'category' => TutorViolationCategory::DirectAnswerSeeking,
        'occurred_at' => now()->subDay(),
    ]);

    GlcTutorAgent::fake([[
        'reply' => 'Here is a hint instead.',
        'violation' => TutorViolationCategory::DirectAnswerSeeking->value,
    ]])->preventStrayPrompts();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Answer please']);

    Notification::assertNothingSent();
});

it('ignores direct-answer violations outside the window', function (): void {
    Notification::fake();

    ['student' => $student] = TutorScenario::assignedStudent();
    prepareTutorGuardrailScenario();

    TutorViolation::factory()->count(2)->create([
        'user_id' => $student->id,
        'category' => TutorViolationCategory::DirectAnswerSeeking,
        'occurred_at' => now()->subDays(8),
    ]);

    GlcTutorAgent::fake([[
        'reply' => 'Here is a hint instead.',
        'violation' => TutorViolationCategory::DirectAnswerSeeking->value,
    ]])->preventStrayPrompts();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Answer please']);

    Notification::assertNothingSent();
});

it('does not notify for non direct-answer categories even at volume', function (): void {
    Notification::fake();

    ['student' => $student] = TutorScenario::assignedStudent();
    prepareTutorGuardrailScenario();

    TutorViolation::factory()->count(3)->create([
        'user_id' => $student->id,
        'category' => TutorViolationCategory::OffTopic,
        'occurred_at' => now()->subDay(),
    ]);

    GlcTutorAgent::fake([[
        'reply' => 'Back to the lesson please.',
        'violation' => TutorViolationCategory::OffTopic->value,
    ]])->preventStrayPrompts();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'What football team do you like?']);

    Notification::assertNothingSent();
});

it('throttles to one database notification per teacher per student per window', function (): void {
    ['student' => $student, 'teacher' => $teacher] = TutorScenario::assignedStudent();
    prepareTutorGuardrailScenario();

    TutorViolation::factory()->count(2)->create([
        'user_id' => $student->id,
        'category' => TutorViolationCategory::DirectAnswerSeeking,
        'occurred_at' => now()->subDays(2),
    ]);

    GlcTutorAgent::fake([[
        'reply' => 'Guidance only, no answers.',
        'violation' => TutorViolationCategory::DirectAnswerSeeking->value,
    ]])->preventStrayPrompts();

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
    prepareTutorGuardrailScenario();

    $secondTeacher = User::factory()->teacher()->create();
    $secondTeacher->assignedStudents()->attach($student);

    TutorViolation::factory()->count(2)->create([
        'user_id' => $student->id,
        'category' => TutorViolationCategory::DirectAnswerSeeking,
        'occurred_at' => now()->subDay(),
    ]);

    GlcTutorAgent::fake([[
        'reply' => 'Guidance only.',
        'violation' => TutorViolationCategory::DirectAnswerSeeking->value,
    ]])->preventStrayPrompts();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'The answer please']);

    Notification::assertSentToTimes($teacher, PersistentDirectAnswerSeekingNotification::class, 1);
    Notification::assertSentToTimes($secondTeacher, PersistentDirectAnswerSeekingNotification::class, 1);
});
