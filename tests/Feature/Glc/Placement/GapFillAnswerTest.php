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

it('saves a gap-fill answer and persists the trimmed text', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);
    $gapFill = glcSeedGapFillQuestion($clips[0]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), [
            'item_id' => $gapFill->id,
            'text' => '  seven  ',
        ])
        ->assertSuccessful()
        ->assertJson(['saved' => true]);

    $answer = $attempt->answers()->where('placement_item_id', $gapFill->id)->sole();

    expect($answer->response)->toBe(['text' => 'seven']);
});

it('overwrites a gap-fill answer on re-save', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);
    $gapFill = glcSeedGapFillQuestion($clips[0]);

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
        'item_id' => $gapFill->id,
        'text' => 'seven',
    ])->assertSuccessful();

    $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
        'item_id' => $gapFill->id,
        'text' => '7 am',
    ])->assertSuccessful();

    $answer = $attempt->answers()->where('placement_item_id', $gapFill->id)->sole();

    expect($answer->response)->toBe(['text' => '7 am']);
});

it('rejects a gap-fill save without text', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);
    $gapFill = glcSeedGapFillQuestion($clips[0]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), ['item_id' => $gapFill->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('text');
});

it('rejects a selected option on a gap-fill item', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);
    $gapFill = glcSeedGapFillQuestion($clips[0]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), [
            'item_id' => $gapFill->id,
            'text' => 'seven',
            'selected' => 0,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('selected');

    expect($attempt->answers()->count())->toBe(0);
});

it('rejects typed text on an MCQ item', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['questions' => $questions] = glcSeedListening(clips: 1, questionsPerClip: 1);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), [
            'item_id' => $questions[0]->id,
            'selected' => 1,
            'text' => 'seven',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('text');

    expect($attempt->answers()->count())->toBe(0);
});

it('rejects gap-fill text over 200 characters', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);
    $gapFill = glcSeedGapFillQuestion($clips[0]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), [
            'item_id' => $gapFill->id,
            'text' => str_repeat('a', 201),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('text');
});

it('rejects gap-fill answers while another section is current', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);
    $gapFill = glcSeedGapFillQuestion($clips[0]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), [
            'item_id' => $gapFill->id,
            'text' => 'seven',
        ])
        ->assertStatus(422);

    expect($attempt->answers()->count())->toBe(0);
});

it('rejects gap-fill answers when the listening section is no longer in progress', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);
    $gapFill = glcSeedGapFillQuestion($clips[0]);

    $attempt->sectionStates()
        ->where('section', PlacementSection::Listening)
        ->update(['status' => PlacementSectionStatus::Completed]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), [
            'item_id' => $gapFill->id,
            'text' => 'seven',
        ])
        ->assertStatus(409);
});

it('saves a two-option true/false MCQ answer and rejects out-of-range choices', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);

    $trueFalse = PlacementItem::factory()->create([
        'section' => PlacementSection::Listening,
        'parent_id' => $clips[0]->id,
        'position' => 50,
        'options' => ['True', 'False'],
        'correct_option' => 1,
    ]);

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
        'item_id' => $trueFalse->id,
        'selected' => 2,
    ])->assertStatus(422);

    $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
        'item_id' => $trueFalse->id,
        'selected' => 1,
    ])->assertSuccessful();

    $answer = $attempt->answers()->where('placement_item_id', $trueFalse->id)->sole();

    expect($answer->response)->toBe(['selected' => 1]);
});

it('never exposes accepted answers or correct options on the listening page', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 2);
    glcSeedGapFillQuestion($clips[0], acceptedAnswers: ['Unmistakable Secret Answer'], position: 3);

    $response = $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/sections/listening')
            ->has('clips.0.questions', 3)
            ->where('clips.0.questions.0.format', 'mcq')
            ->where('clips.0.questions.2.format', 'gap_fill')
            ->where('clips.0.questions.2.options', [])
            ->missing('clips.0.questions.2.settings')
            ->missing('clips.0.questions.2.accepted_answers')
            ->missing('clips.0.questions.0.correct_option'));

    $props = (array) $response->viewData('page')['props'];

    glcAssertNoForbiddenKeys($props);

    expect(json_encode($props))->not->toContain('Unmistakable Secret Answer');
});

it('restores saved gap-fill and MCQ answers in the listening page props', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips, 'questions' => $questions] = glcSeedListening(clips: 1, questionsPerClip: 1);
    $gapFill = glcSeedGapFillQuestion($clips[0]);

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
        'item_id' => $questions[0]->id,
        'selected' => 1,
    ])->assertSuccessful();

    $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
        'item_id' => $gapFill->id,
        'text' => 'seven thirty',
    ])->assertSuccessful();

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page
            ->where('answers.'.$questions[0]->id, 1)
            ->where('answers.'.$gapFill->id, 'seven thirty'));
});
