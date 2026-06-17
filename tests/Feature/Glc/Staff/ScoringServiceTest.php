<?php

declare(strict_types=1);

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Enums\SettingKey;
use App\Models\Glc\PlacementAiDraft;
use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementReview;
use App\Models\Glc\PlacementScore;
use App\Models\Setting;
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

it('uses custom section weights and level bands when scoring an attempt', function (): void {
    Setting::set(SettingKey::GlcPlacementScoringSettings, json_encode([
        'section_weights' => [
            'reading' => 0.50,
            'grammar_vocabulary' => 0.30,
            'listening' => 0.10,
            'writing' => 0.05,
            'speaking' => 0.05,
        ],
        'level_band_minimums' => [
            'beginner' => 10.0,
            'elementary' => 25.0,
            'pre_intermediate' => 40.0,
            'intermediate' => 55.0,
            'upper_intermediate' => 70.0,
            'advanced' => 85.0,
        ],
    ]));

    $attempt = PlacementAttempt::factory()->submitted()->create();

    buildObjectiveSection($attempt, PlacementSection::Reading, total: 10, correct: 8);
    buildObjectiveSection($attempt, PlacementSection::GrammarVocabulary, total: 10, correct: 6);

    $score = app(ScoringService::class)->scoreAttempt($attempt);

    expect((float) $score->composite)->toBe(72.5)
        ->and($score->suggested_level)->toBe(GlcLevel::UpperIntermediate);
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

function gapFillQuestion(array $acceptedAnswers): PlacementItem
{
    return PlacementItem::factory()->create([
        'section' => PlacementSection::Listening,
        'type' => PlacementItemType::Question,
        'parent_id' => null,
        'options' => null,
        'correct_option' => null,
        'settings' => ['format' => 'gap_fill', 'accepted_answers' => $acceptedAnswers],
    ]);
}

it('grades gap fill answers against accepted answers ignoring case and extra whitespace', function (string $text, bool $expected): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();
    $item = gapFillQuestion(['platform 9', 'nine']);

    $answer = PlacementAnswer::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $item->id,
        'response' => ['text' => $text],
        'is_correct' => null,
    ]);

    app(ScoringService::class)->scoreAttempt($attempt);

    expect($answer->refresh()->is_correct)->toBe($expected);
})->with([
    'exact match' => ['platform 9', true],
    'different case' => ['Platform 9', true],
    'surrounding and doubled internal whitespace' => ["  PLATFORM   9 \n", true],
    'another accepted answer' => ['Nine', true],
    'wrong answer' => ['platform 13', false],
    'empty answer' => ['', false],
]);

it('counts gap fill items in the objective section percentage', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();

    buildObjectiveSection($attempt, PlacementSection::Listening, total: 3, correct: 3);

    $answeredGapFill = gapFillQuestion(['nine']);
    gapFillQuestion(['ten']);

    PlacementAnswer::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $answeredGapFill->id,
        'response' => ['text' => ' NINE '],
        'is_correct' => null,
    ]);

    $score = app(ScoringService::class)->scoreAttempt($attempt);

    expect((float) $score->section_scores['listening'])->toBe(80.0);
});

it('does not treat a gap fill without accepted answers as gradable', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();

    $broken = PlacementItem::factory()->create([
        'section' => PlacementSection::Listening,
        'type' => PlacementItemType::Question,
        'parent_id' => null,
        'options' => null,
        'correct_option' => null,
        'settings' => ['format' => 'gap_fill', 'accepted_answers' => []],
    ]);

    $answer = PlacementAnswer::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $broken->id,
        'response' => ['text' => 'anything'],
        'is_correct' => null,
    ]);

    $score = app(ScoringService::class)->scoreAttempt($attempt);

    expect($answer->refresh()->is_correct)->toBeNull()
        ->and($score->section_scores['listening'])->toBeNull();
});

it('is safely re-callable and upserts a single score row', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();

    buildObjectiveSection($attempt, PlacementSection::Reading, total: 10, correct: 7);

    app(ScoringService::class)->scoreAttempt($attempt);
    app(ScoringService::class)->scoreAttempt($attempt);

    expect(PlacementScore::query()->where('placement_attempt_id', $attempt->id)->count())->toBe(1);
});
