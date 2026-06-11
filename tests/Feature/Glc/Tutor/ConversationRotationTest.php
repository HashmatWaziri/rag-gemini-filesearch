<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\Setting;
use App\Services\Glc\Tutor\GlcTutorAgent;
use App\Services\Glc\Tutor\TutorConversationSummarizerAgent;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\Fixtures\Glc\TutorScenario;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
    config([
        'gemini.api_key' => 'test-key',
        'ai.providers.gemini.key' => 'test-key',
    ]);
});

function seedTutorPairs(TutorConversation $conversation, int $pairs): void
{
    for ($i = 1; $i <= $pairs; $i++) {
        TutorMessage::factory()->create([
            'tutor_conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Question '.$i,
        ]);
        TutorMessage::factory()->assistant()->create([
            'tutor_conversation_id' => $conversation->id,
            'content' => 'Answer '.$i,
        ]);
    }
}

function prepareTutorRotationScenario(): void
{
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');
}

it('summarizes and flags the oldest 20 pairs once the conversation exceeds 40 pairs', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();
    prepareTutorRotationScenario();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id, 'summary' => null]);
    seedTutorPairs($conversation, 41);

    GlcTutorAgent::fake([['reply' => 'Here is more guidance.', 'violation' => null]])->preventStrayPrompts();
    TutorConversationSummarizerAgent::fake([['summary' => 'SUMMARY: the student practiced verb tenses.']])->preventStrayPrompts();

    actingAs($student)
        ->post(route('tutor.messages.store', $conversation), ['message' => 'One more question'])
        ->assertRedirect();

    $conversation->refresh();

    expect($conversation->summary)->toContain('SUMMARY: the student practiced verb tenses.');

    $messages = $conversation->messages()->orderBy('id')->get();
    $rotated = $messages->filter(fn (TutorMessage $message): bool => (bool) data_get($message->metadata, 'rotated', false));

    expect($messages)->toHaveCount(84)
        ->and($rotated)->toHaveCount(40)
        ->and($rotated->pluck('id')->all())->toBe($messages->take(40)->pluck('id')->all());
});

it('keeps the summary in context for subsequent messages', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();
    prepareTutorRotationScenario();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id, 'summary' => null]);
    seedTutorPairs($conversation, 41);

    GlcTutorAgent::fake([
        ['reply' => 'Guidance one.', 'violation' => null],
        ['reply' => 'Guidance two.', 'violation' => null],
    ])->preventStrayPrompts();
    TutorConversationSummarizerAgent::fake([['summary' => 'SUMMARY: tenses covered.']])->preventStrayPrompts();

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'First']);

    $conversation->refresh();

    expect($conversation->summary)->toContain('SUMMARY: tenses covered.');

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Second']);

    GlcTutorAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === 'Second');

    expect($conversation->messages()->where('role', 'assistant')->orderByDesc('id')->value('content'))
        ->toBe('Guidance two.');
});

it('does not rotate below the threshold', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();
    prepareTutorRotationScenario();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id, 'summary' => null]);
    seedTutorPairs($conversation, 10);

    GlcTutorAgent::fake([['reply' => 'Some guidance.', 'violation' => null]])->preventStrayPrompts();
    TutorConversationSummarizerAgent::fake()->preventStrayPrompts();

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Hello']);

    TutorConversationSummarizerAgent::assertNeverPrompted();

    expect($conversation->refresh()->summary)->toBeNull()
        ->and($conversation->messages()->get()
            ->filter(fn (TutorMessage $message): bool => (bool) data_get($message->metadata, 'rotated', false)))
        ->toHaveCount(0);
});

it('keeps messages in active context when the summarize call fails', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();
    prepareTutorRotationScenario();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id, 'summary' => null]);
    seedTutorPairs($conversation, 41);

    GlcTutorAgent::fake([['reply' => 'Guidance.', 'violation' => null]])->preventStrayPrompts();
    TutorConversationSummarizerAgent::fake(function (): never {
        throw new RuntimeException('unavailable');
    })->preventStrayPrompts();

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Hello']);

    $conversation->refresh();

    expect($conversation->summary)->toBeNull()
        ->and($conversation->messages()->get()
            ->filter(fn (TutorMessage $message): bool => (bool) data_get($message->metadata, 'rotated', false)))
        ->toHaveCount(0);
});
