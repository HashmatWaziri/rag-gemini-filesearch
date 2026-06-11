<?php

declare(strict_types=1);

use App\Models\Glc\WritingSubmission;
use App\Models\User;
use App\Services\Glc\Tutor\TutorWritingCorrectionAgent;
use App\Services\Glc\Tutor\WritingCorrectionService;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
    config([
        'gemini.api_key' => 'test-key',
        'ai.providers.gemini.key' => 'test-key',
    ]);
});

const WRITING_SAMPLE = 'I goes to school every day. My teacher is very kind and the lessons are interesting to me.';

/**
 * @param  array<string, mixed>  $overrides
 */
function fakeWritingCorrectionAgent(array $overrides = []): void
{
    $default = [
        'dimensions' => [
            'grammar' => ['score' => 3, 'comment' => 'Watch your verb tenses.'],
            'vocabulary' => ['score' => 4, 'comment' => 'Good word variety.'],
            'structure' => ['score' => 3, 'comment' => 'Add clearer paragraphs.'],
            'coherence' => ['score' => 4, 'comment' => 'Ideas connect well.'],
            'task_completion' => ['score' => 5, 'comment' => 'Fully addresses the task.'],
        ],
        'summary' => 'A solid attempt. Focus on grammar accuracy next.',
        'highlights' => [
            ['start' => 0, 'end' => 5, 'type' => 'grammar', 'comment' => 'Check the verb form here.'],
        ],
    ];

    $payload = array_replace($default, $overrides);

    TutorWritingCorrectionAgent::fake([$payload])->preventStrayPrompts();
}

it('evaluates a submission into five dimensions with highlights', function (): void {
    $student = User::factory()->student()->create();

    fakeWritingCorrectionAgent();

    actingAs($student)
        ->post(route('tutor.writing.store'), ['text' => WRITING_SAMPLE])
        ->assertRedirect();

    $submission = WritingSubmission::query()->sole();

    expect($submission->status)->toBe('completed')
        ->and($submission->user_id)->toBe($student->id)
        ->and($submission->error)->toBeNull();

    $dimensions = data_get($submission->feedback, 'dimensions');

    expect(array_keys($dimensions))->toBe(['grammar', 'vocabulary', 'structure', 'coherence', 'task_completion'])
        ->and($dimensions['grammar'])->toMatchArray(['score' => 3, 'comment' => 'Watch your verb tenses.'])
        ->and(data_get($submission->feedback, 'summary'))->toBe('A solid attempt. Focus on grammar accuracy next.')
        ->and($submission->highlights)->toBe([
            ['start' => 0, 'end' => 5, 'type' => 'grammar', 'comment' => 'Check the verb form here.'],
        ]);
});

it('clamps dimension scores into the 1-5 range', function (): void {
    $student = User::factory()->student()->create();

    fakeWritingCorrectionAgent([
        'dimensions' => [
            'grammar' => ['score' => 9, 'comment' => 'High.'],
            'vocabulary' => ['score' => 0, 'comment' => 'Low.'],
            'structure' => ['score' => 3, 'comment' => 'Mid.'],
            'coherence' => ['score' => 4, 'comment' => 'Good.'],
            'task_completion' => ['score' => 5, 'comment' => 'Done.'],
        ],
    ]);

    actingAs($student)->post(route('tutor.writing.store'), ['text' => WRITING_SAMPLE]);

    $dimensions = data_get(WritingSubmission::query()->sole()->feedback, 'dimensions');

    expect($dimensions['grammar']['score'])->toBe(5)
        ->and($dimensions['vocabulary']['score'])->toBe(1);
});

it('drops highlights with invalid offsets or unknown types', function (): void {
    $student = User::factory()->student()->create();

    fakeWritingCorrectionAgent([
        'highlights' => [
            ['start' => 0, 'end' => 5, 'type' => 'grammar', 'comment' => 'Valid.'],
            ['start' => 2, 'end' => 4, 'type' => 'grammar', 'comment' => 'Overlaps previous.'],
            ['start' => 50, 'end' => 5000, 'type' => 'grammar', 'comment' => 'Out of range.'],
            ['start' => 10, 'end' => 15, 'type' => 'letter_grade', 'comment' => 'Unknown type.'],
            ['start' => 20, 'end' => 18, 'type' => 'vocabulary', 'comment' => 'Inverted.'],
        ],
    ]);

    actingAs($student)->post(route('tutor.writing.store'), ['text' => WRITING_SAMPLE]);

    expect(WritingSubmission::query()->sole()->highlights)->toBe([
        ['start' => 0, 'end' => 5, 'type' => 'grammar', 'comment' => 'Valid.'],
    ]);
});

it('marks the submission failed with a friendly error when a dimension is missing', function (): void {
    $student = User::factory()->student()->create();

    fakeWritingCorrectionAgent([
        'dimensions' => [
            'grammar' => ['score' => 3, 'comment' => 'Only one dimension.'],
        ],
    ]);

    actingAs($student)->post(route('tutor.writing.store'), ['text' => WRITING_SAMPLE]);

    $submission = WritingSubmission::query()->sole();

    expect($submission->status)->toBe('failed')
        ->and($submission->error)->toBe(WritingCorrectionService::FAILURE_MESSAGE);
});

it('marks the submission failed when the provider errors', function (): void {
    $student = User::factory()->student()->create();

    TutorWritingCorrectionAgent::fake(function (): never {
        throw new RuntimeException('boom');
    })->preventStrayPrompts();

    actingAs($student)->post(route('tutor.writing.store'), ['text' => WRITING_SAMPLE]);

    $submission = WritingSubmission::query()->sole();

    expect($submission->status)->toBe('failed')
        ->and($submission->error)->toBe(WritingCorrectionService::FAILURE_MESSAGE);
});

it('marks the submission failed when the API key is missing', function (): void {
    config(['gemini.api_key' => null, 'ai.providers.gemini.key' => null]);

    $student = User::factory()->student()->create();

    TutorWritingCorrectionAgent::fake()->preventStrayPrompts();

    actingAs($student)->post(route('tutor.writing.store'), ['text' => WRITING_SAMPLE]);

    expect(WritingSubmission::query()->sole()->status)->toBe('failed');
    TutorWritingCorrectionAgent::assertNeverPrompted();
});

it('renders the correction detail with dimension scores and no letter grade or band', function (): void {
    $student = User::factory()->student()->create();
    $submission = WritingSubmission::factory()->completed()->create(['user_id' => $student->id]);

    $response = actingAs($student)
        ->get(route('tutor.writing.show', $submission))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/writing/show')
            ->has('submission.feedback.dimensions', 5)
            ->has('submission.highlights', 1)
            ->missing('submission.feedback.grade')
            ->missing('submission.feedback.band'));

    expect($response->getContent())
        ->not->toContain('IELTS')
        ->not->toContain('"grade"')
        ->not->toContain('"band"');
});

it('lists only the student\'s own submissions', function (): void {
    $student = User::factory()->student()->create();
    $other = User::factory()->student()->create();

    WritingSubmission::factory()->count(2)->create(['user_id' => $student->id]);
    WritingSubmission::factory()->create(['user_id' => $other->id]);

    actingAs($student)
        ->get(route('tutor.writing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/writing/index')
            ->has('submissions', 2));
});

it('blocks access to another student\'s submission', function (): void {
    $student = User::factory()->student()->create();
    $other = User::factory()->student()->create();

    $submission = WritingSubmission::factory()->create(['user_id' => $other->id]);

    actingAs($student)->get(route('tutor.writing.show', $submission))->assertForbidden();
});

it('rejects writing that is too short', function (): void {
    $student = User::factory()->student()->create();

    actingAs($student)
        ->post(route('tutor.writing.store'), ['text' => 'Too short'])
        ->assertSessionHasErrors('text');
});

it('redirects unconsented minors away from the writing flow', function (): void {
    $minor = User::factory()->minorStudent()->create();

    actingAs($minor)
        ->get(route('tutor.writing.index'))
        ->assertRedirect(route('tutor.blocked'));
});
