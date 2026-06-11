<?php

declare(strict_types=1);

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAiDraft;
use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementReview;
use App\Models\Glc\PlacementScore;
use App\Services\Glc\Review\ScoringService;

function buildObjectiveSection(
    PlacementAttempt $attempt,
    PlacementSection $section,
    int $total,
    int $correct,
    ?int $wrong = null,
): void {
    $wrong ??= $total - $correct;

    $items = PlacementItem::factory()->count($total)->create([
        'section' => $section,
        'type' => PlacementItemType::Question,
        'parent_id' => null,
        'correct_option' => 1,
    ]);

    $items->take($correct)->each(function (PlacementItem $item) use ($attempt): void {
        PlacementAnswer::factory()->create([
            'placement_attempt_id' => $attempt->id,
            'placement_item_id' => $item->id,
            'response' => ['selected' => 1],
            'is_correct' => null,
        ]);
    });

    $items->skip($correct)->take($wrong)->each(function (PlacementItem $item) use ($attempt): void {
        PlacementAnswer::factory()->create([
            'placement_attempt_id' => $attempt->id,
            'placement_item_id' => $item->id,
            'response' => ['selected' => 3],
            'is_correct' => null,
        ]);
    });
}

it('grades objective answers and computes exact per-section percentages', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();

    buildObjectiveSection($attempt, PlacementSection::Reading, total: 12, correct: 8, wrong: 3);
    buildObjectiveSection($attempt, PlacementSection::GrammarVocabulary, total: 22, correct: 16);
    buildObjectiveSection($attempt, PlacementSection::Listening, total: 10, correct: 6);

    $score = app(ScoringService::class)->scoreAttempt($attempt);

    expect((float) $score->section_scores['reading'])->toBe(66.67)
        ->and((float) $score->section_scores['grammar_vocabulary'])->toBe(72.73)
        ->and((float) $score->section_scores['listening'])->toBe(60.0)
        ->and($score->section_scores['writing'])->toBeNull()
        ->and($score->section_scores['speaking'])->toBeNull();

    expect($attempt->answers()->where('is_correct', true)->count())->toBe(8 + 16 + 6)
        ->and($attempt->answers()->where('is_correct', false)->count())->toBe(3 + 6 + 4);
});

it('computes the composite proportionally over available sections when drafts are missing', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();

    buildObjectiveSection($attempt, PlacementSection::Reading, total: 12, correct: 8);
    buildObjectiveSection($attempt, PlacementSection::GrammarVocabulary, total: 22, correct: 16);
    buildObjectiveSection($attempt, PlacementSection::Listening, total: 10, correct: 6);

    $score = app(ScoringService::class)->scoreAttempt($attempt);

    expect((float) $score->composite)->toBe(66.47)
        ->and($score->suggested_level)->toBe(GlcLevel::Intermediate)
        ->and($score->computed_at)->not->toBeNull();
});

it('includes completed AI draft percentages with equal weights and maps the GLC level', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();

    buildObjectiveSection($attempt, PlacementSection::Reading, total: 12, correct: 8);
    buildObjectiveSection($attempt, PlacementSection::GrammarVocabulary, total: 22, correct: 16);
    buildObjectiveSection($attempt, PlacementSection::Listening, total: 10, correct: 6);

    PlacementAiDraft::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'section' => PlacementSection::Writing,
        'dimension_scores' => ['grammar' => 3, 'vocabulary' => 3, 'structure' => 4, 'coherence' => 3, 'task_completion' => 3],
    ]);

    PlacementAiDraft::factory()->speaking()->create([
        'placement_attempt_id' => $attempt->id,
        'dimension_scores' => ['grammar' => 3, 'vocabulary' => 3, 'structure' => 3, 'coherence' => 2, 'task_completion' => 3],
    ]);

    $score = app(ScoringService::class)->scoreAttempt($attempt);

    expect((float) $score->section_scores['writing'])->toBe(64.0)
        ->and((float) $score->section_scores['speaking'])->toBe(56.0)
        ->and((float) $score->composite)->toBe(63.88)
        ->and($score->suggested_level)->toBe(GlcLevel::Intermediate)
        ->and($score->variance_flagged)->toBeFalse();
});

it('ignores pending and failed drafts when scoring', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();

    buildObjectiveSection($attempt, PlacementSection::Reading, total: 10, correct: 5);

    PlacementAiDraft::factory()->pending()->create([
        'placement_attempt_id' => $attempt->id,
        'section' => PlacementSection::Writing,
    ]);
    PlacementAiDraft::factory()->failed()->speaking()->create([
        'placement_attempt_id' => $attempt->id,
    ]);

    $score = app(ScoringService::class)->scoreAttempt($attempt);

    expect($score->section_scores['writing'])->toBeNull()
        ->and($score->section_scores['speaking'])->toBeNull()
        ->and((float) $score->composite)->toBe(50.0);
});

it('flags high cross-section variance and adds the variance review flag', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();
    $review = PlacementReview::factory()->create(['placement_attempt_id' => $attempt->id]);

    buildObjectiveSection($attempt, PlacementSection::Reading, total: 5, correct: 5);
    buildObjectiveSection($attempt, PlacementSection::GrammarVocabulary, total: 4, correct: 1);
    $score = app(ScoringService::class)->scoreAttempt($attempt);

    expect($score->variance_flagged)->toBeTrue()
        ->and($review->refresh()->hasFlag('variance'))->toBeTrue();
});

it('does not flag variance below the configured threshold', function (): void {
    config(['glc.placement.variance_flag_threshold' => 30.0]);

    $attempt = PlacementAttempt::factory()->submitted()->create();

    buildObjectiveSection($attempt, PlacementSection::Reading, total: 10, correct: 8);
    buildObjectiveSection($attempt, PlacementSection::GrammarVocabulary, total: 10, correct: 6);
    expect(app(ScoringService::class)->scoreAttempt($attempt)->variance_flagged)->toBeFalse();
});

it('maps composites onto all seven GLC levels', function (float $composite, GlcLevel $expected): void {
    expect(GlcLevel::fromComposite($composite))->toBe($expected);
})->with([
    [5.0, GlcLevel::Starter],
    [20.0, GlcLevel::Beginner],
    [40.0, GlcLevel::Elementary],
    [50.0, GlcLevel::PreIntermediate],
    [70.0, GlcLevel::Intermediate],
    [80.0, GlcLevel::UpperIntermediate],
    [95.0, GlcLevel::Advanced],
]);

it('is safely re-callable and upserts a single score row', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();

    buildObjectiveSection($attempt, PlacementSection::Reading, total: 10, correct: 7);

    app(ScoringService::class)->scoreAttempt($attempt);
    app(ScoringService::class)->scoreAttempt($attempt);

    expect(PlacementScore::query()->where('placement_attempt_id', $attempt->id)->count())->toBe(1);
});
