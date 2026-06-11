<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\Setting;
use App\Services\Glc\Tutor\TutorChatService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\Glc\GeminiFake;
use Tests\Fixtures\Glc\TutorScenario;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
    config(['gemini.api_key' => 'test-key']);
});

it('creates a new conversation and redirects to the chat screen', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();

    $response = actingAs($student)->post(route('tutor.conversations.store'));

    $conversation = TutorConversation::query()->sole();

    $response->assertRedirect(route('tutor.conversations.show', $conversation));
    expect($conversation->user_id)->toBe($student->id);
});

it('sends a message through the scoped retrieval pipeline and persists the reply', function (): void {
    ['student' => $student, 'assignment' => $assignment, 'course' => $course, 'level' => $level, 'unit' => $unit] = TutorScenario::assignedStudent();

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    CurriculumDocument::factory()->published()->create([
        'course_id' => $course->id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(
            GeminiFake::chat('Past simple describes finished actions.', null, ['Unit Worksheet']),
        ),
    ]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id, 'title' => null]);

    actingAs($student)
        ->post(route('tutor.messages.store', $conversation), ['message' => 'Explain the past simple tense please'])
        ->assertRedirect(route('tutor.conversations.show', $conversation));

    $messages = $conversation->messages()->orderBy('id')->get();

    expect($messages)->toHaveCount(2)
        ->and($messages[0]->role)->toBe('user')
        ->and($messages[0]->content)->toBe('Explain the past simple tense please')
        ->and($messages[1]->role)->toBe('assistant')
        ->and($messages[1]->content)->toBe('Past simple describes finished actions.')
        ->and($messages[1]->metadata)->toMatchArray(['citations' => ['Unit Worksheet']]);

    $conversation->refresh();

    expect($conversation->title)->toBe('Explain the past simple tense please')
        ->and($conversation->last_activity_at?->toDateTimeString())->toBe(now()->toDateTimeString());

    Http::assertSent(function (Request $request) use ($assignment): bool {
        $data = $request->data();

        $expectedFilter = sprintf(
            'course_id=%d AND course_level_id=%d AND course_unit_id=%d AND status="published"',
            $assignment->course_id,
            $assignment->course_level_id,
            $assignment->course_unit_id,
        );

        return str_contains($request->url(), 'models/gemini-2.5-flash:generateContent')
            && $request->hasHeader('x-goog-api-key', 'test-key')
            && str_contains((string) data_get($data, 'system_instruction.parts.0.text'), 'English only')
            && data_get($data, 'tools.0.file_search.file_search_store_names') === ['fileSearchStores/glc-test-store']
            && data_get($data, 'tools.0.file_search.metadata_filter') === $expectedFilter
            && data_get($data, 'generationConfig.responseMimeType') === 'application/json'
            && data_get($data, 'contents.0.parts.0.text') === 'Explain the past simple tense please';
    });
});

it('omits the file search tool when no published documents are in scope', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(GeminiFake::chat('Hello!')),
    ]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Hi']);

    Http::assertSent(fn (Request $request): bool => data_get($request->data(), 'tools') === null);
});

it('resumes a thread with prior messages and the rotation summary as context', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(GeminiFake::chat('Sure, continuing.')),
    ]);

    $conversation = TutorConversation::factory()->create([
        'user_id' => $student->id,
        'summary' => 'Student practiced irregular verbs.',
    ]);

    TutorMessage::factory()->create([
        'tutor_conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'What is an irregular verb?',
    ]);
    TutorMessage::factory()->assistant()->create([
        'tutor_conversation_id' => $conversation->id,
        'content' => 'A verb that does not take -ed.',
    ]);
    TutorMessage::factory()->create([
        'tutor_conversation_id' => $conversation->id,
        'role' => 'user',
        'content' => 'Old rotated question',
        'metadata' => ['rotated' => true],
    ]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Give me another example']);

    Http::assertSent(function (Request $request): bool {
        $contents = data_get($request->data(), 'contents');
        $texts = collect($contents)->map(fn (array $content): string => (string) data_get($content, 'parts.0.text'));

        return str_contains($texts->first(), 'Student practiced irregular verbs.')
            && $texts->contains('What is an irregular verb?')
            && $texts->contains('A verb that does not take -ed.')
            && $texts->contains('Give me another example')
            && ! $texts->contains('Old rotated question');
    });
});

it('shows students their own message history', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);
    TutorMessage::factory()->create(['tutor_conversation_id' => $conversation->id, 'content' => 'My question']);
    TutorMessage::factory()->assistant()->create(['tutor_conversation_id' => $conversation->id, 'content' => 'My answer']);

    actingAs($student)
        ->get(route('tutor.conversations.show', $conversation))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/chat')
            ->has('messages', 2)
            ->where('messages.0.content', 'My question')
            ->where('messages.1.content', 'My answer'));
});

it('blocks students from another student\'s conversation', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();
    $other = TutorScenario::assignedStudent();

    $foreignConversation = TutorConversation::factory()->create(['user_id' => $other['student']->id]);

    actingAs($student)->get(route('tutor.conversations.show', $foreignConversation))->assertForbidden();
    actingAs($student)->post(route('tutor.messages.store', $foreignConversation), ['message' => 'Hello'])->assertForbidden();
});

it('lists only the student\'s own conversations', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();
    $other = TutorScenario::assignedStudent();

    TutorConversation::factory()->count(2)->create(['user_id' => $student->id]);
    TutorConversation::factory()->create(['user_id' => $other['student']->id]);

    actingAs($student)
        ->get(route('tutor.index'))
        ->assertInertia(fn ($page) => $page->has('conversations', 2));
});

it('replies with a friendly message when the API key is missing', function (): void {
    config(['gemini.api_key' => null]);

    ['student' => $student] = TutorScenario::assignedStudent();
    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)
        ->post(route('tutor.messages.store', $conversation), ['message' => 'Help me with grammar'])
        ->assertRedirect();

    $assistant = $conversation->messages()->where('role', 'assistant')->sole();

    expect($assistant->content)->toBe(TutorChatService::UNAVAILABLE_MESSAGE);
    Http::assertNothingSent();
});

it('replies with a friendly message when the provider fails', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();

    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'boom'], 500)]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)
        ->post(route('tutor.messages.store', $conversation), ['message' => 'Help me with grammar'])
        ->assertRedirect();

    expect($conversation->messages()->where('role', 'assistant')->sole()->content)
        ->toBe(TutorChatService::UNAVAILABLE_MESSAGE);
});

it('treats non-JSON model output as a plain reply without violation', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response(GeminiFake::text('Just plain prose, no JSON.')),
    ]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Hello']);

    $assistant = $conversation->messages()->where('role', 'assistant')->sole();

    expect($assistant->content)->toBe('Just plain prose, no JSON.')
        ->and(data_get($assistant->metadata, 'violation'))->toBeNull();
});

it('rejects an empty message', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();
    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)
        ->post(route('tutor.messages.store', $conversation), ['message' => ''])
        ->assertSessionHasErrors('message');
});
