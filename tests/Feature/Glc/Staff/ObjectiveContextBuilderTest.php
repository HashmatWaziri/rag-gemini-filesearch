<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Services\Glc\Review\ObjectiveContextBuilder;

it('serializes objective sections with questions, answers, correctness, and the section percentage', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();

    $reading = PlacementItem::factory()->count(2)->sequence(
        ['body' => 'Choose the synonym of happy.', 'position' => 1],
        ['body' => 'What did the author imply?', 'position' => 2],
    )->create([
        'section' => PlacementSection::Reading,
        'type' => PlacementItemType::Question,
        'options' => ['glad', 'sad', 'angry', 'tired'],
        'correct_option' => 0,
    ]);

    PlacementAnswer::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $reading[0]->id,
        'response' => ['selected' => 0],
    ]);

    PlacementAnswer::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $reading[1]->id,
        'response' => ['selected' => 1],
    ]);

    PlacementItem::factory()->create([
        'section' => PlacementSection::Listening,
        'type' => PlacementItemType::Question,
        'body' => 'Where does the speaker live?',
        'options' => ['London', 'Cairo'],
        'correct_option' => 1,
    ]);

    $context = app(ObjectiveContextBuilder::class)->build($attempt);

    expect($context)
        ->toContain('Reading — auto-scored from the question bank: 1/2 correct (50%)')
        ->toContain('Q1: Choose the synonym of happy. | Options: A) glad B) sad C) angry D) tired | Candidate answered: A) glad | Correct answer: A) glad | correct')
        ->toContain('Q2: What did the author imply? | Options: A) glad B) sad C) angry D) tired | Candidate answered: B) sad | Correct answer: A) glad | incorrect')
        ->toContain('Listening — auto-scored from the question bank: 0/1 correct (0%)')
        ->toContain('Candidate answered: no answer | Correct answer: B) Cairo | incorrect');
});

it('summarizes per-section correct/total counts and percentages', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();

    $items = PlacementItem::factory()->count(3)->create([
        'section' => PlacementSection::GrammarVocabulary,
        'type' => PlacementItemType::Question,
        'correct_option' => 2,
    ]);

    PlacementAnswer::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $items[0]->id,
        'response' => ['selected' => 2],
    ]);

    PlacementAnswer::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $items[1]->id,
        'response' => ['selected' => 0],
    ]);

    $summary = app(ObjectiveContextBuilder::class)->summary($attempt);

    expect($summary)->toHaveKey('grammar_vocabulary')
        ->and($summary['grammar_vocabulary'])->toBe([
            'correct' => 1,
            'total' => 3,
            'percentage' => 33.33,
        ])
        ->and($summary)->not->toHaveKey('reading');
});

it('reports when no objective sections are available', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();

    expect(app(ObjectiveContextBuilder::class)->build($attempt))
        ->toBe('No objective sections were available for this attempt.');
});
