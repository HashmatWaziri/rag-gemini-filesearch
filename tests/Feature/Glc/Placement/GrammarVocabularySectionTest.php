<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;

require_once __DIR__.'/PlacementTestHelpers.php';

beforeEach(function (): void {
    $this->withoutVite();
    $this->withCredentials();
});

it('shows grammar/vocabulary as the second section after reading completes', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    glcSeedGrammarVocabulary(3);

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->post(route('placement.section.complete'), [
        'section' => PlacementSection::Reading->value,
    ]);

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/sections/grammar-vocabulary')
            ->where('progress.current', PlacementSection::GrammarVocabulary->value)
            ->where('progress.currentIndex', 2)
            ->where('progress.sections.0.status', PlacementSectionStatus::Completed->value)
            ->where('progress.sections.1.status', PlacementSectionStatus::InProgress->value));
});

it('cannot be opened before reading is finished', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    glcSeedGrammarVocabulary(2);
    glcSeedReading(passages: 1, questionsPerPassage: 1);

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page->component('glc/placement/sections/reading'));
});

it('renders the configured standalone items with their option formats', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::GrammarVocabulary);

    glcSeedGrammarVocabulary(3);

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page
            ->has('questions', 3)
            ->has('questions.0.options', 4)
            ->where('timer.timeLimitSeconds', 720));
});

it('auto-saves grammar/vocabulary answers and restores them on resume', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::GrammarVocabulary);
    $questions = glcSeedGrammarVocabulary(3);

    $cookie = glcPlacementCookie($attempt);

    foreach ([0, 1, 2] as $index) {
        $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
            'item_id' => $questions[$index]->id,
            'selected' => $index,
        ])->assertSuccessful();
    }

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page
            ->where('answers.'.$questions[0]->id, 0)
            ->where('answers.'.$questions[1]->id, 1)
            ->where('answers.'.$questions[2]->id, 2));
});

it('accumulates time against grammar/vocabulary only while it is current', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::GrammarVocabulary);

    $this->travel(40)->seconds();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.heartbeat'))
        ->assertSuccessful()
        ->assertJson(['timeUsedSeconds' => 40, 'remainingSeconds' => 680]);

    expect($attempt->sectionStates()->where('section', PlacementSection::Listening)->sole()->time_used_seconds)
        ->toBe(0);
});

it('completing grammar/vocabulary unlocks listening', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::GrammarVocabulary);

    $this->withCookies(glcPlacementCookie($attempt))
        ->post(route('placement.section.complete'), ['section' => PlacementSection::GrammarVocabulary->value])
        ->assertRedirect(route('placement.test'));

    $attempt->refresh();

    expect($attempt->current_section)->toBe(PlacementSection::Listening)
        ->and($attempt->sectionStates()->where('section', PlacementSection::Listening)->sole()->status)
        ->toBe(PlacementSectionStatus::InProgress);
});
