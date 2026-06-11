<?php

declare(strict_types=1);

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementAiDraftStatus;
use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementReviewStatus;
use App\Enums\Glc\PlacementSection;
use App\Jobs\Glc\Placement\GeneratePlacementAiDraftsJob;
use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementReview;
use App\Services\Glc\Admin\SpeakingEvaluationGuidelines;
use App\Services\Glc\Admin\WritingEvaluationGuidelines;
use App\Services\Glc\Ai\PlacementAiSettings;
use App\Services\Glc\Review\PlacementRecommendationAgent;
use App\Services\Glc\Review\SpeakingEvaluationAgent;
use App\Services\Glc\Review\WritingEvaluationAgent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Prompts\TranscriptionPrompt;
use Laravel\Ai\Transcription;

function attemptWithEssayAndRecording(): PlacementAttempt
{
    Storage::fake('local');

    $attempt = PlacementAttempt::factory()->submitted()->create();

    $writingPrompt = PlacementItem::factory()->writingPrompt()->create();
    $speakingPrompt = PlacementItem::factory()->speakingPrompt()->create();

    PlacementAnswer::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $writingPrompt->id,
        'response' => ['text' => 'I prefer studying English online because it is flexible and I can repeat lessons.'],
        'word_count' => 14,
    ]);

    Storage::disk('local')->put('glc/placement/recordings/sample.webm', 'fake-audio-bytes');

    PlacementAnswer::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $speakingPrompt->id,
        'response' => [
            'audio_path' => 'glc/placement/recordings/sample.webm',
            'duration_seconds' => 95,
            'mime_type' => 'audio/webm',
        ],
        'recording_attempts' => 1,
    ]);

    return $attempt;
}

function seedReadingQuestions(PlacementAttempt $attempt): void
{
    $items = PlacementItem::factory()->count(2)->sequence(
        ['body' => 'What color is the sky?', 'position' => 1],
        ['body' => 'How many legs does a cat have?', 'position' => 2],
    )->create([
        'section' => PlacementSection::Reading,
        'type' => PlacementItemType::Question,
        'options' => ['Blue', 'Green', 'Red', 'Yellow'],
        'correct_option' => 0,
    ]);

    PlacementAnswer::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $items[0]->id,
        'response' => ['selected' => 0],
    ]);

    PlacementAnswer::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $items[1]->id,
        'response' => ['selected' => 2],
    ]);
}

/**
 * @return array<string, mixed>
 */
function fakeEvaluationPayload(): array
{
    return [
        'dimension_scores' => [
            'grammar' => 3,
            'vocabulary' => 4,
            'structure' => 3,
            'coherence' => 3,
            'task_completion' => 4,
        ],
        'feedback' => 'Generally clear with minor grammar slips.',
        'confidence' => 'medium',
    ];
}

/**
 * @return array<string, mixed>
 */
function fakeSpeakingEvaluationPayload(): array
{
    return [
        'dimension_scores' => [
            'fluency' => 3,
            'grammar' => 3,
            'vocabulary' => 3,
            'task_completion' => 3,
            'comprehensibility' => 3,
        ],
        'feedback' => 'Connected ideas with some hesitation visible in the transcript.',
        'confidence' => 'medium',
    ];
}

/**
 * @return array<string, mixed>
 */
function fakeRecommendationPayload(): array
{
    $skillLevels = [];
    $skillSummaries = [];

    foreach (PlacementSection::ordered() as $section) {
        $skillLevels[$section->value] = GlcLevel::Intermediate->value;
        $skillSummaries[$section->value] = sprintf('Solid %s performance for this level.', $section->label());
    }

    return [
        'recommended_level' => GlcLevel::Intermediate->value,
        'skill_levels' => $skillLevels,
        'skill_summaries' => $skillSummaries,
        'confidence' => 'medium',
        'rationale' => 'Consistent performance across the productive and objective sections.',
    ];
}

function fakeAllPlacementAgents(): void
{
    WritingEvaluationAgent::fake([fakeEvaluationPayload()])->preventStrayPrompts();
    SpeakingEvaluationAgent::fake([fakeSpeakingEvaluationPayload()])->preventStrayPrompts();
    PlacementRecommendationAgent::fake([fakeRecommendationPayload()])->preventStrayPrompts();
    Transcription::fake(['I would like to describe my hometown.'])->preventStrayTranscriptions();
}

function runDraftsJob(int $attemptId): void
{
    new GeneratePlacementAiDraftsJob($attemptId)->handle(
        app(App\Services\Glc\Review\AiDraftService::class),
        app(App\Services\Glc\Review\ScoringService::class),
        app(App\Services\Glc\Review\PlacementRecommendationService::class),
    );
}

it('is queueable with an attempt id constructor', function (): void {
    Queue::fake();

    dispatch(new GeneratePlacementAiDraftsJob(123));

    Queue::assertPushed(GeneratePlacementAiDraftsJob::class);
});

it('generates scored writing and speaking drafts, recomputing the score across all sections', function (): void {
    Http::fake();
    fakeAllPlacementAgents();

    $attempt = attemptWithEssayAndRecording();

    runDraftsJob($attempt->id);

    $writing = $attempt->aiDrafts()->where('section', PlacementSection::Writing)->firstOrFail();
    $speaking = $attempt->aiDrafts()->where('section', PlacementSection::Speaking)->firstOrFail();

    expect($writing->status)->toBe(PlacementAiDraftStatus::Completed)
        ->and($writing->dimension_scores)->toBe([
            'grammar' => 3, 'vocabulary' => 4, 'structure' => 3, 'coherence' => 3, 'task_completion' => 4,
        ])
        ->and($writing->confidence)->toBe('medium')
        ->and($writing->feedback)->toBe('Generally clear with minor grammar slips.')
        ->and($writing->generated_at)->not->toBeNull()
        ->and($speaking->status)->toBe(PlacementAiDraftStatus::Completed)
        ->and($speaking->transcript)->toBe('I would like to describe my hometown.')
        ->and($speaking->dimension_scores)->toBe([
            'fluency' => 3, 'grammar' => 3, 'vocabulary' => 3, 'task_completion' => 3, 'comprehensibility' => 3,
        ])
        ->and($speaking->feedback)->toBe('Connected ideas with some hesitation visible in the transcript.')
        ->and($speaking->confidence)->toBe('medium');

    $score = $attempt->score()->firstOrFail();
    expect((float) $score->section_scores['writing'])->toBe(68.0)
        ->and((float) $score->section_scores['speaking'])->toBe(60.0)
        ->and((float) $score->composite)->toBe(64.0);

    WritingEvaluationAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Candidate essay'));
    SpeakingEvaluationAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Candidate speaking transcript')
        && $prompt->contains('I would like to describe my hometown.'));

    Transcription::assertGenerated(fn (TranscriptionPrompt $prompt): bool => $prompt->audio->path === 'glc/placement/recordings/sample.webm');

    Http::assertNothingSent();
});

it('judges the essay against the writing guidelines with the objective sections as context', function (): void {
    fakeAllPlacementAgents();

    $attempt = attemptWithEssayAndRecording();
    seedReadingQuestions($attempt);

    runDraftsJob($attempt->id);

    WritingEvaluationAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('GLC writing evaluation guidelines')
        && $prompt->contains('1. Grammar accuracy:')
        && $prompt->contains('5. Task completion:'));

    WritingEvaluationAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('do not let it override your assessment of the essay itself')
        && $prompt->contains('Reading — auto-scored from the question bank: 1/2 correct (50%)')
        && $prompt->contains('What color is the sky?')
        && $prompt->contains('Candidate answered: C) Red | Correct answer: A) Blue | incorrect'));
});

it('judges the speaking transcript against the speaking guidelines', function (): void {
    fakeAllPlacementAgents();

    $attempt = attemptWithEssayAndRecording();

    runDraftsJob($attempt->id);

    SpeakingEvaluationAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('GLC speaking evaluation guidelines')
        && $prompt->contains('1. Fluency and coherence:')
        && $prompt->contains('5. Comprehensibility:')
        && $prompt->contains('do not let it override your assessment of the spoken response itself'));
});

it('embeds Admin-customized guidelines in the writing and speaking evaluation prompts', function (): void {
    app(WritingEvaluationGuidelines::class)->update([
        ['title' => 'Idiomatic range', 'description' => 'Natural use of common English idioms.'],
        ['title' => 'Spelling', 'description' => 'Accurate spelling throughout.'],
    ]);
    app(SpeakingEvaluationGuidelines::class)->update([
        ['title' => 'Storytelling', 'description' => 'Engaging narrative structure in spoken answers.'],
    ]);

    fakeAllPlacementAgents();

    $attempt = attemptWithEssayAndRecording();

    runDraftsJob($attempt->id);

    WritingEvaluationAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('1. Idiomatic range: Natural use of common English idioms.')
        && $prompt->contains('2. Spelling: Accurate spelling throughout.'));
    WritingEvaluationAgent::assertNotPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Grammar accuracy'));

    SpeakingEvaluationAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('1. Storytelling: Engaging narrative structure in spoken answers.'));
    SpeakingEvaluationAgent::assertNotPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Fluency and coherence'));
});

it('uses the default Admin-selected provider and model for evaluation and transcription', function (): void {
    fakeAllPlacementAgents();

    $attempt = attemptWithEssayAndRecording();

    runDraftsJob($attempt->id);

    WritingEvaluationAgent::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->provider->name() === 'gemini' && $prompt->model === 'gemini-2.5-flash'
    );

    SpeakingEvaluationAgent::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->provider->name() === 'gemini' && $prompt->model === 'gemini-2.5-flash'
    );

    Transcription::assertGenerated(
        fn (TranscriptionPrompt $prompt): bool => $prompt->provider->name() === 'gemini' && $prompt->model === 'gemini-2.5-flash'
    );
});

it('honors an updated Admin provider and model selection', function (): void {
    $settings = app(PlacementAiSettings::class);
    $settings->updateSelection(PlacementAiSettings::TASK_WRITING, 'openai', 'gpt-5.4-mini');
    $settings->updateSelection(PlacementAiSettings::TASK_SPEAKING_EVALUATION, 'deepseek', 'deepseek-v4-flash');
    $settings->updateSelection(PlacementAiSettings::TASK_SPEAKING, 'groq-stt', 'whisper-large-v3');

    fakeAllPlacementAgents();

    $attempt = attemptWithEssayAndRecording();

    runDraftsJob($attempt->id);

    WritingEvaluationAgent::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->provider->name() === 'openai' && $prompt->model === 'gpt-5.4-mini'
    );

    SpeakingEvaluationAgent::assertPrompted(
        fn (AgentPrompt $prompt): bool => $prompt->provider->name() === 'deepseek' && $prompt->model === 'deepseek-v4-flash'
    );

    Transcription::assertGenerated(
        fn (TranscriptionPrompt $prompt): bool => $prompt->provider->name() === 'groq-stt' && $prompt->model === 'whisper-large-v3'
    );
});

it('stores a completed holistic recommendation with per-skill levels and summaries', function (): void {
    fakeAllPlacementAgents();

    $attempt = attemptWithEssayAndRecording();
    seedReadingQuestions($attempt);

    runDraftsJob($attempt->id);

    $recommendation = $attempt->aiRecommendation()->firstOrFail();

    expect($recommendation->status)->toBe(PlacementAiDraftStatus::Completed)
        ->and($recommendation->recommended_level)->toBe(GlcLevel::Intermediate)
        ->and($recommendation->skill_levels['speaking'])->toBe('intermediate')
        ->and($recommendation->skill_summaries['reading'])->toBe('Solid Reading performance for this level.')
        ->and($recommendation->confidence)->toBe('medium')
        ->and($recommendation->rationale)->not->toBeNull()
        ->and($recommendation->generated_at)->not->toBeNull();

    PlacementRecommendationAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('GLC writing evaluation guidelines')
        && $prompt->contains('GLC speaking evaluation guidelines')
        && $prompt->contains('Reading — auto-scored from the question bank')
        && $prompt->contains('Generally clear with minor grammar slips.')
        && $prompt->contains('I would like to describe my hometown.'));
});

it('records a failed recommendation without blocking the drafts when the recommendation agent fails', function (): void {
    WritingEvaluationAgent::fake([fakeEvaluationPayload()])->preventStrayPrompts();
    SpeakingEvaluationAgent::fake([fakeSpeakingEvaluationPayload()])->preventStrayPrompts();
    PlacementRecommendationAgent::fake()->preventStrayPrompts();
    Transcription::fake(['I would like to describe my hometown.'])->preventStrayTranscriptions();

    $attempt = attemptWithEssayAndRecording();

    runDraftsJob($attempt->id);

    $recommendation = $attempt->aiRecommendation()->firstOrFail();

    expect($recommendation->status)->toBe(PlacementAiDraftStatus::Failed)
        ->and($recommendation->recommended_level)->toBeNull()
        ->and($recommendation->error)->not->toBeNull();

    expect($attempt->aiDrafts()->where('status', PlacementAiDraftStatus::Completed)->count())->toBe(2);
});

it('marks drafts failed when AI generation fails and never throws', function (): void {
    Http::fake();
    WritingEvaluationAgent::fake()->preventStrayPrompts();
    SpeakingEvaluationAgent::fake()->preventStrayPrompts();
    PlacementRecommendationAgent::fake()->preventStrayPrompts();
    Transcription::fake()->preventStrayTranscriptions();

    $attempt = attemptWithEssayAndRecording();

    runDraftsJob($attempt->id);

    expect($attempt->aiDrafts()->count())->toBe(2);

    $attempt->aiDrafts->each(function ($draft): void {
        expect($draft->status)->toBe(PlacementAiDraftStatus::Failed)
            ->and($draft->error)->not->toBeNull();
    });

    Http::assertNothingSent();
});

it('keeps the transcript when transcription succeeds but the speaking evaluation fails', function (): void {
    WritingEvaluationAgent::fake([fakeEvaluationPayload()])->preventStrayPrompts();
    SpeakingEvaluationAgent::fake()->preventStrayPrompts();
    PlacementRecommendationAgent::fake([fakeRecommendationPayload()])->preventStrayPrompts();
    Transcription::fake(['I would like to describe my hometown.'])->preventStrayTranscriptions();

    $attempt = attemptWithEssayAndRecording();

    runDraftsJob($attempt->id);

    $speaking = $attempt->aiDrafts()->where('section', PlacementSection::Speaking)->firstOrFail();

    expect($speaking->status)->toBe(PlacementAiDraftStatus::Failed)
        ->and($speaking->transcript)->toBe('I would like to describe my hometown.')
        ->and($speaking->dimension_scores)->toBeNull()
        ->and($speaking->error)->not->toBeNull();

    $score = $attempt->score()->firstOrFail();
    expect($score->section_scores['speaking'])->toBeNull();
});

it('marks the speaking draft failed when transcription returns an empty transcript', function (): void {
    WritingEvaluationAgent::fake([fakeEvaluationPayload()])->preventStrayPrompts();
    SpeakingEvaluationAgent::fake()->preventStrayPrompts();
    PlacementRecommendationAgent::fake([fakeRecommendationPayload()])->preventStrayPrompts();
    Transcription::fake(['   '])->preventStrayTranscriptions();

    $attempt = attemptWithEssayAndRecording();

    runDraftsJob($attempt->id);

    $speaking = $attempt->aiDrafts()->where('section', PlacementSection::Speaking)->firstOrFail();

    expect($speaking->status)->toBe(PlacementAiDraftStatus::Failed)
        ->and($speaking->transcript)->toBeNull()
        ->and($speaking->error)->toContain('empty transcript');

    SpeakingEvaluationAgent::assertNeverPrompted();
});

it('never auto-approves or releases results when drafts fail', function (): void {
    Http::fake();
    WritingEvaluationAgent::fake()->preventStrayPrompts();
    SpeakingEvaluationAgent::fake()->preventStrayPrompts();
    PlacementRecommendationAgent::fake()->preventStrayPrompts();
    Transcription::fake()->preventStrayTranscriptions();

    $attempt = attemptWithEssayAndRecording();
    $review = PlacementReview::factory()->create(['placement_attempt_id' => $attempt->id]);

    runDraftsJob($attempt->id);

    expect($review->refresh()->status)->toBe(PlacementReviewStatus::Pending)
        ->and($attempt->resultLinks()->count())->toBe(0);
});

it('records a failed writing draft when the essay is missing', function (): void {
    WritingEvaluationAgent::fake()->preventStrayPrompts();
    SpeakingEvaluationAgent::fake()->preventStrayPrompts();
    PlacementRecommendationAgent::fake([fakeRecommendationPayload()])->preventStrayPrompts();
    Transcription::fake()->preventStrayTranscriptions();
    Storage::fake('local');

    $attempt = PlacementAttempt::factory()->submitted()->create();
    PlacementItem::factory()->writingPrompt()->create();

    runDraftsJob($attempt->id);

    $writing = $attempt->aiDrafts()->where('section', PlacementSection::Writing)->firstOrFail();

    expect($writing->status)->toBe(PlacementAiDraftStatus::Failed)
        ->and($writing->error)->toContain('essay');

    WritingEvaluationAgent::assertNeverPrompted();
});

it('handles a vanished attempt gracefully', function (): void {
    Http::fake();
    WritingEvaluationAgent::fake()->preventStrayPrompts();
    SpeakingEvaluationAgent::fake()->preventStrayPrompts();
    PlacementRecommendationAgent::fake()->preventStrayPrompts();
    Transcription::fake()->preventStrayTranscriptions();

    runDraftsJob(999999);

    WritingEvaluationAgent::assertNeverPrompted();
    SpeakingEvaluationAgent::assertNeverPrompted();
    PlacementRecommendationAgent::assertNeverPrompted();
    Transcription::assertNothingGenerated();
    Http::assertNothingSent();
});
