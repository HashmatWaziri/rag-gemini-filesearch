<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLesson;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\Setting;
use App\Services\Glc\Tutor\GlcTutorAgent;
use App\Services\Glc\Tutor\TutorChatService;
use App\Services\Glc\Tutor\TutorCitationExtractor;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Providers\Tools\FileSearch;
use Laravel\Ai\Responses\AgentResponse;
use Tests\Fixtures\Glc\TutorScenario;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
    config([
        'gemini.api_key' => 'test-key',
        'ai.providers.gemini.key' => 'test-key',
    ]);
});

function fakeTutorAgent(array $responses = [['reply' => 'Past simple describes finished actions.', 'violation' => null]]): void
{
    GlcTutorAgent::fake($responses)->preventStrayPrompts();
}

function fakeTutorCitations(array $titles): void
{
    app()->instance(TutorCitationExtractor::class, new class($titles) extends TutorCitationExtractor
    {
        /** @param list<string> $titles */
        public function __construct(private array $titles) {}

        public function titlesFromResponse(AgentResponse $response): array
        {
            return $this->titles;
        }
    });
}

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
        'title' => 'Unit Worksheet',
        'version' => 2,
    ]);

    fakeTutorCitations(['Unit Worksheet']);
    fakeTutorAgent();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id, 'title' => null]);

    actingAs($student)
        ->post(route('tutor.messages.store', $conversation), ['message' => 'Explain the past simple tense please'])
        ->assertRedirect(route('tutor.conversations.show', $conversation));

    $messages = $conversation->messages()->orderBy('id')->get();

    $document = CurriculumDocument::query()->where('title', 'Unit Worksheet')->sole();

    expect($messages)->toHaveCount(2)
        ->and($messages[0]->role)->toBe('user')
        ->and($messages[0]->content)->toBe('Explain the past simple tense please')
        ->and($messages[1]->role)->toBe('assistant')
        ->and($messages[1]->content)->toBe('Past simple describes finished actions.')
        ->and($messages[1]->metadata)->toMatchArray([
            'citations' => [sprintf('Unit Worksheet (%s / %s / Unit-wide)', $course->name, $unit->name)],
            'curriculum_sources' => [[
                'document_id' => $document->id,
                'version' => 2,
                'title' => 'Unit Worksheet',
            ]],
        ]);

    $conversation->refresh();

    expect($conversation->title)->toBe('Explain the past simple tense please')
        ->and($conversation->last_activity_at?->toDateTimeString())->toBe(now()->toDateTimeString());

    GlcTutorAgent::assertPrompted(function (AgentPrompt $prompt) use ($assignment): bool {
        $agent = $prompt->agent;

        if (! $agent instanceof GlcTutorAgent) {
            return false;
        }

        $tools = [...$agent->tools()];
        $fileSearch = $tools[0] ?? null;

        return $prompt->prompt === 'Explain the past simple tense please'
            && str_contains($agent->instructions(), 'English only')
            && $fileSearch instanceof FileSearch
            && $fileSearch->filters === [
                ['type' => 'eq', 'key' => 'course_id', 'value' => $assignment->course_id],
                ['type' => 'eq', 'key' => 'course_level_id', 'value' => $assignment->course_level_id],
                ['type' => 'eq', 'key' => 'course_unit_id', 'value' => $assignment->course_unit_id],
                ['type' => 'eq', 'key' => 'status', 'value' => 'published'],
            ];
    });
});

it('blocks the exchange before any model call when no published documents are in scope', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent(withMaterials: false);

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    GlcTutorAgent::fake()->preventStrayPrompts();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)
        ->post(route('tutor.messages.store', $conversation), ['message' => 'Can you teach me something?'])
        ->assertRedirect(route('tutor.conversations.show', $conversation));

    $messages = $conversation->messages()->orderBy('id')->get();

    expect($messages)->toHaveCount(2)
        ->and($messages[1]->role)->toBe('assistant')
        ->and($messages[1]->content)->toBe(TutorChatService::MATERIALS_NOT_READY_MESSAGE);

    GlcTutorAgent::assertNeverPrompted();
});

it('still blocks the exchange when only draft or archived documents are in scope', function (): void {
    ['student' => $student, 'course' => $course, 'level' => $level, 'unit' => $unit] = TutorScenario::assignedStudent(withMaterials: false);

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    $scope = [
        'course_id' => $course->id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
    ];

    CurriculumDocument::factory()->create($scope);
    CurriculumDocument::factory()->archived()->create($scope);

    GlcTutorAgent::fake()->preventStrayPrompts();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Hello?']);

    expect($conversation->messages()->where('role', 'assistant')->sole()->content)
        ->toBe(TutorChatService::MATERIALS_NOT_READY_MESSAGE);

    GlcTutorAgent::assertNeverPrompted();
});

it('blocks the exchange when publishing materials are not yet indexed', function (): void {
    ['student' => $student, 'course' => $course, 'level' => $level, 'unit' => $unit] = TutorScenario::assignedStudent(withMaterials: false);

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    CurriculumDocument::factory()->publishing()->create([
        'course_id' => $course->id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
    ]);

    GlcTutorAgent::fake()->preventStrayPrompts();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Hello?']);

    expect($conversation->messages()->where('role', 'assistant')->sole()->content)
        ->toBe(TutorChatService::MATERIALS_NOT_READY_MESSAGE);

    GlcTutorAgent::assertNeverPrompted();
});

it('formats grounded citations as title with course, unit, and lesson or Unit-wide', function (): void {
    ['student' => $student, 'course' => $course, 'level' => $level, 'unit' => $unit] = TutorScenario::assignedStudent(withMaterials: false);

    $lesson = CourseLesson::factory()->for($unit, 'unit')->create(['name' => 'Past Simple']);

    $scope = [
        'course_id' => $course->id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
    ];

    CurriculumDocument::factory()->published()->create([...$scope, 'course_lesson_id' => null, 'title' => 'Grammar Basics', 'version' => 1]);
    CurriculumDocument::factory()->published()->create([...$scope, 'course_lesson_id' => $lesson->id, 'title' => 'Lesson 2 Worksheet', 'version' => 3]);

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    fakeTutorCitations(['Grammar Basics', 'Lesson 2 Worksheet']);
    fakeTutorAgent([['reply' => 'Grounded explanation.', 'violation' => null]]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Explain please']);

    $expected = [
        sprintf('Grammar Basics (%s / %s / Unit-wide)', $course->name, $unit->name),
        sprintf('Lesson 2 Worksheet (%s / %s / Past Simple)', $course->name, $unit->name),
    ];

    $grammarDocument = CurriculumDocument::query()->where('title', 'Grammar Basics')->sole();
    $worksheetDocument = CurriculumDocument::query()->where('title', 'Lesson 2 Worksheet')->sole();

    expect(data_get($conversation->messages()->where('role', 'assistant')->sole()->metadata, 'citations'))
        ->toBe($expected)
        ->and(data_get($conversation->messages()->where('role', 'assistant')->sole()->metadata, 'curriculum_sources'))
        ->toBe([
            [
                'document_id' => $grammarDocument->id,
                'version' => 1,
                'title' => 'Grammar Basics',
            ],
            [
                'document_id' => $worksheetDocument->id,
                'version' => 3,
                'title' => 'Lesson 2 Worksheet',
            ],
        ]);

    actingAs($student)
        ->get(route('tutor.conversations.show', $conversation))
        ->assertInertia(fn ($page) => $page
            ->where('messages.1.citations.0', $expected[0])
            ->where('messages.1.citations.1', $expected[1])
            ->where('messages.1.curriculum_sources.0.document_id', $grammarDocument->id)
            ->where('messages.1.curriculum_sources.1.version', 3));
});

it('omits unmatched citation titles from curriculum_sources but keeps the formatted label', function (): void {
    ['student' => $student, 'course' => $course, 'level' => $level, 'unit' => $unit] = TutorScenario::assignedStudent(withMaterials: false);

    $scope = [
        'course_id' => $course->id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
    ];

    CurriculumDocument::factory()->published()->create([...$scope, 'title' => 'Known Worksheet', 'version' => 1]);

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    fakeTutorCitations(['Known Worksheet', 'Unknown From Gemini']);
    fakeTutorAgent([['reply' => 'Grounded explanation.', 'violation' => null]]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Explain please']);

    $knownDocument = CurriculumDocument::query()->where('title', 'Known Worksheet')->sole();
    $metadata = $conversation->messages()->where('role', 'assistant')->sole()->metadata;

    expect(data_get($metadata, 'citations'))->toBe([
        sprintf('Known Worksheet (%s / %s / Unit-wide)', $course->name, $unit->name),
        sprintf('Unknown From Gemini (%s / %s / Unit-wide)', $course->name, $unit->name),
    ])->and(data_get($metadata, 'curriculum_sources'))->toBe([
        [
            'document_id' => $knownDocument->id,
            'version' => 1,
            'title' => 'Known Worksheet',
        ],
    ]);
});

it('scopes the filter to the unit so unit-wide and lesson-specific documents are both retrievable', function (): void {
    ['student' => $student, 'assignment' => $assignment, 'course' => $course, 'level' => $level, 'unit' => $unit] = TutorScenario::assignedStudent(withMaterials: false);

    $lesson = CourseLesson::factory()->for($unit, 'unit')->create();

    $scope = [
        'course_id' => $course->id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
    ];

    CurriculumDocument::factory()->published()->create([...$scope, 'course_lesson_id' => null]);
    CurriculumDocument::factory()->published()->create([...$scope, 'course_lesson_id' => $lesson->id]);

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    fakeTutorAgent([['reply' => 'Sure.', 'violation' => null]]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Hi']);

    GlcTutorAgent::assertPrompted(function (AgentPrompt $prompt) use ($assignment): bool {
        $tools = [...$prompt->agent->tools()];
        $filters = $tools[0] instanceof FileSearch ? $tools[0]->filters : [];

        return collect($filters)->contains(fn (array $filter): bool => $filter['key'] === 'course_unit_id'
            && $filter['value'] === $assignment->course_unit_id)
            && ! collect($filters)->contains(fn (array $filter): bool => $filter['key'] === 'course_lesson_id');
    });
});

it('uses the latest teacher assignment for the next message in an existing conversation', function (): void {
    ['student' => $student, 'teacher' => $teacher, 'assignment' => $assignment] = TutorScenario::assignedStudent();

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    $newCourse = Course::factory()->create();
    $newLevel = CourseLevel::factory()->for($newCourse)->create();
    $newUnit = CourseUnit::factory()->for($newLevel, 'level')->create();

    CurriculumDocument::factory()->published()->create([
        'course_id' => $newCourse->id,
        'course_level_id' => $newLevel->id,
        'course_unit_id' => $newUnit->id,
    ]);

    fakeTutorAgent([
        ['reply' => 'First hint.', 'violation' => null],
        ['reply' => 'Second hint.', 'violation' => null],
    ]);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'First question']);

    actingAs($teacher)
        ->put(route('staff.students.assignment.update', $student), [
            'course_id' => $newCourse->id,
            'course_level_id' => $newLevel->id,
            'course_unit_id' => $newUnit->id,
        ])
        ->assertRedirect();

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Second question']);

    GlcTutorAgent::assertPrompted(function (AgentPrompt $prompt) use ($assignment): bool {
        if ($prompt->prompt !== 'First question' || ! $prompt->agent instanceof GlcTutorAgent) {
            return false;
        }

        $filters = ($prompt->agent->tools()[0] ?? null) instanceof FileSearch
            ? $prompt->agent->tools()[0]->filters
            : [];

        return ($filters[0]['value'] ?? null) === $assignment->course_id;
    });

    GlcTutorAgent::assertPrompted(function (AgentPrompt $prompt) use ($newCourse): bool {
        if ($prompt->prompt !== 'Second question' || ! $prompt->agent instanceof GlcTutorAgent) {
            return false;
        }

        $filters = ($prompt->agent->tools()[0] ?? null) instanceof FileSearch
            ? $prompt->agent->tools()[0]->filters
            : [];

        return ($filters[0]['value'] ?? null) === $newCourse->id;
    });

    expect($conversation->messages()->count())->toBe(4);
});

it('resumes a thread with prior messages and the rotation summary as context', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    fakeTutorAgent([['reply' => 'Sure, continuing.', 'violation' => null]]);

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

    GlcTutorAgent::assertPrompted(function (AgentPrompt $prompt): bool {
        if (! $prompt->agent instanceof GlcTutorAgent) {
            return false;
        }

        $messages = [...$prompt->agent->messages()];
        $text = collect($messages)
            ->map(fn ($message): string => $message->content ?? '')
            ->implode("\n");

        return str_contains($text, 'Student practiced irregular verbs.')
            && str_contains($text, 'What is an irregular verb?')
            && str_contains($text, 'A verb that does not take -ed.')
            && ! str_contains($text, 'Old rotated question');
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
    config(['gemini.api_key' => null, 'ai.providers.gemini.key' => null]);

    ['student' => $student] = TutorScenario::assignedStudent();
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    GlcTutorAgent::fake()->preventStrayPrompts();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)
        ->post(route('tutor.messages.store', $conversation), ['message' => 'Help me with grammar'])
        ->assertRedirect();

    $assistant = $conversation->messages()->where('role', 'assistant')->sole();

    expect($assistant->content)->toBe(TutorChatService::UNAVAILABLE_MESSAGE);
    GlcTutorAgent::assertNeverPrompted();
});

it('replies with a friendly message when the provider fails', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    GlcTutorAgent::fake(function (): never {
        throw new RuntimeException('boom');
    })->preventStrayPrompts();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)
        ->post(route('tutor.messages.store', $conversation), ['message' => 'Help me with grammar'])
        ->assertRedirect();

    expect($conversation->messages()->where('role', 'assistant')->sole()->content)
        ->toBe(TutorChatService::UNAVAILABLE_MESSAGE);
});

it('replies with a friendly message when structured output is invalid', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-test-store');

    GlcTutorAgent::fake([['violation' => null]])->preventStrayPrompts();

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->post(route('tutor.messages.store', $conversation), ['message' => 'Hello']);

    expect($conversation->messages()->where('role', 'assistant')->sole()->content)
        ->toBe(TutorChatService::UNAVAILABLE_MESSAGE);
});

it('rejects an empty message', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();
    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)
        ->post(route('tutor.messages.store', $conversation), ['message' => ''])
        ->assertSessionHasErrors('message');
});
