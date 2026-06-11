<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementAiDraftStatus;
use App\Enums\Glc\PlacementReviewStatus;
use App\Enums\Glc\PlacementSection;
use App\Jobs\Glc\Placement\GeneratePlacementAiDraftsJob;
use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementReview;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

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

function fakeGeminiEvaluation(): void
{
    config(['gemini.api_key' => 'test-key']);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode([
                        'transcript' => 'I would like to describe my hometown.',
                        'dimension_scores' => [
                            'grammar' => 3,
                            'vocabulary' => 4,
                            'structure' => 3,
                            'coherence' => 3,
                            'task_completion' => 4,
                        ],
                        'feedback' => 'Generally clear with minor grammar slips.',
                        'confidence' => 'medium',
                    ]),
                ]]],
            ]],
        ]),
    ]);
}

it('is queueable with an attempt id constructor', function (): void {
    Queue::fake();

    dispatch(new GeneratePlacementAiDraftsJob(123));

    Queue::assertPushed(GeneratePlacementAiDraftsJob::class);
});

it('generates completed writing and speaking drafts and recomputes the score', function (): void {
    fakeGeminiEvaluation();
    $attempt = attemptWithEssayAndRecording();

    new GeneratePlacementAiDraftsJob($attempt->id)->handle(
        app(App\Services\Glc\Review\AiDraftService::class),
        app(App\Services\Glc\Review\ScoringService::class),
    );

    $writing = $attempt->aiDrafts()->where('section', PlacementSection::Writing)->firstOrFail();
    $speaking = $attempt->aiDrafts()->where('section', PlacementSection::Speaking)->firstOrFail();

    expect($writing->status)->toBe(PlacementAiDraftStatus::Completed)
        ->and($writing->dimension_scores)->toBe([
            'grammar' => 3, 'vocabulary' => 4, 'structure' => 3, 'coherence' => 3, 'task_completion' => 4,
        ])
        ->and($writing->confidence)->toBe('medium')
        ->and($writing->generated_at)->not->toBeNull()
        ->and($speaking->status)->toBe(PlacementAiDraftStatus::Completed)
        ->and($speaking->transcript)->toBe('I would like to describe my hometown.');

    $score = $attempt->score()->firstOrFail();
    expect((float) $score->section_scores['writing'])->toBe(68.0)
        ->and((float) $score->section_scores['speaking'])->toBe(68.0);

    Http::assertSent(function ($request): bool {
        $parts = $request->data()['contents'][0]['parts'] ?? [];

        foreach ($parts as $part) {
            if (isset($part['inlineData']['data'])) {
                return $part['inlineData']['mimeType'] === 'audio/webm'
                    && $part['inlineData']['data'] === base64_encode('fake-audio-bytes');
            }
        }

        return false;
    });
});

it('marks drafts failed when GEMINI_API_KEY is missing and never throws', function (): void {
    config(['gemini.api_key' => null]);
    Http::fake();

    $attempt = attemptWithEssayAndRecording();

    new GeneratePlacementAiDraftsJob($attempt->id)->handle(
        app(App\Services\Glc\Review\AiDraftService::class),
        app(App\Services\Glc\Review\ScoringService::class),
    );

    expect($attempt->aiDrafts()->count())->toBe(2);

    $attempt->aiDrafts->each(function ($draft): void {
        expect($draft->status)->toBe(PlacementAiDraftStatus::Failed)
            ->and($draft->error)->toContain('GEMINI_API_KEY');
    });

    Http::assertNothingSent();
});

it('marks drafts failed on HTTP errors', function (): void {
    config(['gemini.api_key' => 'test-key']);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'boom'], 500)]);

    $attempt = attemptWithEssayAndRecording();

    new GeneratePlacementAiDraftsJob($attempt->id)->handle(
        app(App\Services\Glc\Review\AiDraftService::class),
        app(App\Services\Glc\Review\ScoringService::class),
    );

    $attempt->aiDrafts->each(function ($draft): void {
        expect($draft->status)->toBe(PlacementAiDraftStatus::Failed)
            ->and($draft->error)->toContain('500');
    });
});

it('marks drafts failed on unparseable JSON responses', function (): void {
    config(['gemini.api_key' => 'test-key']);
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'this is not json']]]]],
        ]),
    ]);

    $attempt = attemptWithEssayAndRecording();

    new GeneratePlacementAiDraftsJob($attempt->id)->handle(
        app(App\Services\Glc\Review\AiDraftService::class),
        app(App\Services\Glc\Review\ScoringService::class),
    );

    $attempt->aiDrafts->each(function ($draft): void {
        expect($draft->status)->toBe(PlacementAiDraftStatus::Failed);
    });
});

it('never auto-approves or releases results when drafts fail', function (): void {
    config(['gemini.api_key' => null]);
    Http::fake();

    $attempt = attemptWithEssayAndRecording();
    $review = PlacementReview::factory()->create(['placement_attempt_id' => $attempt->id]);

    new GeneratePlacementAiDraftsJob($attempt->id)->handle(
        app(App\Services\Glc\Review\AiDraftService::class),
        app(App\Services\Glc\Review\ScoringService::class),
    );

    expect($review->refresh()->status)->toBe(PlacementReviewStatus::Pending)
        ->and($attempt->resultLinks()->count())->toBe(0);
});

it('records a failed writing draft when the essay is missing', function (): void {
    config(['gemini.api_key' => 'test-key']);
    Http::fake();
    Storage::fake('local');

    $attempt = PlacementAttempt::factory()->submitted()->create();
    PlacementItem::factory()->writingPrompt()->create();

    new GeneratePlacementAiDraftsJob($attempt->id)->handle(
        app(App\Services\Glc\Review\AiDraftService::class),
        app(App\Services\Glc\Review\ScoringService::class),
    );

    $writing = $attempt->aiDrafts()->where('section', PlacementSection::Writing)->firstOrFail();

    expect($writing->status)->toBe(PlacementAiDraftStatus::Failed)
        ->and($writing->error)->toContain('essay');
});

it('handles a vanished attempt gracefully', function (): void {
    Http::fake();

    new GeneratePlacementAiDraftsJob(999999)->handle(
        app(App\Services\Glc\Review\AiDraftService::class),
        app(App\Services\Glc\Review\ScoringService::class),
    );

    Http::assertNothingSent();
});
