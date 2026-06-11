<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Models\Glc\PlacementItem;

require_once __DIR__.'/PlacementTestHelpers.php';

beforeEach(function (): void {
    $this->withoutVite();
    $this->withCredentials();
});

it('shows reading as the first section after the device check', function (): void {
    $attempt = glcOnboardingAttempt(['instructions_acknowledged_at' => now()]);
    glcSeedReading();

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->post(route('placement.device-check.confirm'), [
        'audio_ok' => true,
        'microphone_ok' => true,
        'recording_ok' => true,
    ]);

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/sections/reading')
            ->where('progress.current', PlacementSection::Reading->value)
            ->where('progress.currentIndex', 1)
            ->where('progress.total', 5));
});

it('renders two passages with their configured MCQs (default 12 total)', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    glcSeedReading(passages: 2, questionsPerPassage: 6);

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/sections/reading')
            ->has('passages', 2)
            ->has('passages.0.questions', 6)
            ->has('passages.1.questions', 6)
            ->has('passages.0.questions.0.options', 4)
            ->where('timer.timeLimitSeconds', 900));
});

it('excludes inactive items from the rendered form', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    ['passages' => $passages] = glcSeedReading(passages: 1, questionsPerPassage: 2);

    PlacementItem::factory()->create([
        'section' => PlacementSection::Reading,
        'parent_id' => $passages[0]->id,
        'position' => 99,
        'is_active' => false,
    ]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page->has('passages.0.questions', 2));
});

it('saves reading answers and restores them on resume', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    ['questions' => $questions] = glcSeedReading(passages: 1, questionsPerPassage: 3);

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
        'item_id' => $questions[0]->id,
        'selected' => 1,
    ])->assertSuccessful();

    $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
        'item_id' => $questions[2]->id,
        'selected' => 3,
    ])->assertSuccessful();

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page
            ->where('answers.'.$questions[0]->id, 1)
            ->where('answers.'.$questions[2]->id, 3)
            ->missing('answers.'.$questions[1]->id));
});

it('advances to grammar/vocabulary when reading is explicitly finished', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    glcSeedReading(passages: 1, questionsPerPassage: 1);
    glcSeedGrammarVocabulary(2);

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)
        ->post(route('placement.section.complete'), ['section' => PlacementSection::Reading->value])
        ->assertRedirect(route('placement.test'));

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/sections/grammar-vocabulary')
            ->where('progress.currentIndex', 2));
});

it('cannot reopen reading after it has been finished', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::GrammarVocabulary);
    ['questions' => $questions] = glcSeedReading(passages: 1, questionsPerPassage: 1);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), [
            'item_id' => $questions[0]->id,
            'selected' => 0,
        ])
        ->assertStatus(422);
});

it('runs the timer for reading only while reading is current', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->travel(30)->seconds();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.heartbeat'))
        ->assertSuccessful();

    $states = $attempt->sectionStates()->get()->keyBy(fn ($state) => $state->section->value);

    expect($states[PlacementSection::Reading->value]->time_used_seconds)->toBe(30)
        ->and($states[PlacementSection::GrammarVocabulary->value]->time_used_seconds)->toBe(0)
        ->and($states[PlacementSection::GrammarVocabulary->value]->status)->toBe(PlacementSectionStatus::Locked);
});
