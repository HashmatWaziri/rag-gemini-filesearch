<?php

declare(strict_types=1);

use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Glc\GeminiFake;
use Tests\Fixtures\Glc\TutorScenario;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
    config(['gemini.api_key' => 'test-key']);
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

it('summarizes and flags the oldest 20 pairs once the conversation exceeds 40 pairs', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id, 'summary' => null]);
    seedTutorPairs($conversation, 41);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(GeminiFake::chat('Here is more guidance.'))
            ->push(GeminiFake::text('SUMMARY: the student practiced verb tenses.')),
    ]);

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

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id, 'summary' => null]);
    seedTutorPairs($conversation, 41);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(GeminiFake::chat('Guidance one.'))
            ->push(GeminiFake::text('SUMMARY: tenses covered.'))
            ->push(GeminiFake::chat('Guidance two.')),
    ]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'First']);
    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Second']);

    Http::assertSentCount(3);
    Http::assertSent(function (Request $request): bool {
        $firstText = (string) data_get($request->data(), 'contents.0.parts.0.text');

        if (! str_contains($firstText, 'Summary of the earlier part of this conversation:')) {
            return false;
        }

        return str_contains($firstText, 'SUMMARY: tenses covered.');
    });
});

it('does not rotate below the threshold', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id, 'summary' => null]);
    seedTutorPairs($conversation, 10);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(GeminiFake::chat('Some guidance.')),
    ]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Hello']);

    Http::assertSentCount(1);

    expect($conversation->refresh()->summary)->toBeNull()
        ->and($conversation->messages()->get()
            ->filter(fn (TutorMessage $message): bool => (bool) data_get($message->metadata, 'rotated', false)))
        ->toHaveCount(0);
});

it('keeps messages in active context when the summarize call fails', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id, 'summary' => null]);
    seedTutorPairs($conversation, 41);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(GeminiFake::chat('Guidance.'))
            ->push(['error' => 'unavailable'], 500),
    ]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Hello']);

    $conversation->refresh();

    expect($conversation->summary)->toBeNull()
        ->and($conversation->messages()->get()
            ->filter(fn (TutorMessage $message): bool => (bool) data_get($message->metadata, 'rotated', false)))
        ->toHaveCount(0);
});
