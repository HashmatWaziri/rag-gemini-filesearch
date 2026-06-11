<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Services\Glc\Placement\PlacementSessionService;

require_once __DIR__.'/PlacementTestHelpers.php';

beforeEach(function (): void {
    $this->withoutVite();
    $this->withCredentials();
});

it('persists an objective answer immediately on selection', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    ['questions' => $questions] = glcSeedReading(passages: 1, questionsPerPassage: 2);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), [
            'item_id' => $questions[0]->id,
            'selected' => 2,
        ])
        ->assertSuccessful()
        ->assertJson(['saved' => true]);

    $this->assertDatabaseHas('placement_answers', [
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $questions[0]->id,
    ]);

    expect($attempt->answers()->sole()->response)->toBe(['selected' => 2]);
});

it('upserts repeated saves for the same item without duplicating rows', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    ['questions' => $questions] = glcSeedReading(passages: 1, questionsPerPassage: 1);

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
        'item_id' => $questions[0]->id,
        'selected' => 0,
    ])->assertSuccessful();

    $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
        'item_id' => $questions[0]->id,
        'selected' => 3,
    ])->assertSuccessful();

    expect($attempt->answers()->count())->toBe(1)
        ->and($attempt->answers()->sole()->response)->toBe(['selected' => 3]);
});

it('rejects an answer for an option outside the question range', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    ['questions' => $questions] = glcSeedReading(passages: 1, questionsPerPassage: 1);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), [
            'item_id' => $questions[0]->id,
            'selected' => 9,
        ])
        ->assertStatus(422);
});

it('autosaves writing text with a server-computed word count', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Writing);
    $prompt = glcSeedWritingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.writing.save'), ['text' => glcEssayText(42)])
        ->assertSuccessful()
        ->assertJson(['saved' => true, 'wordCount' => 42]);

    $answer = $attempt->answers()->sole();

    expect($answer->placement_item_id)->toBe($prompt->id)
        ->and($answer->word_count)->toBe(42);
});

it('keeps the latest writing save on repeated autosaves', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Writing);
    glcSeedWritingPrompt();

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.writing.save'), ['text' => 'first draft']);
    $this->withCookies($cookie)->postJson(route('placement.writing.save'), ['text' => 'second longer draft here']);

    expect($attempt->answers()->count())->toBe(1)
        ->and($attempt->answers()->sole()->response['text'])->toBe('second longer draft here')
        ->and($attempt->answers()->sole()->word_count)->toBe(4);
});

it('resumes exactly where the candidate left off with saved answers prefilled', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::GrammarVocabulary);
    $questions = glcSeedGrammarVocabulary(3);

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
        'item_id' => $questions[1]->id,
        'selected' => 1,
    ])->assertSuccessful();

    $this->travel(2)->hours();

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/sections/grammar-vocabulary')
            ->where('progress.current', PlacementSection::GrammarVocabulary->value)
            ->where('progress.currentIndex', 2)
            ->where('answers.'.$questions[1]->id, 1));
});

it('shows the expired screen when resuming after the 24-hour window', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->travel(25)->hours();

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertRedirect(route('placement.expired'));

    $this->get(route('placement.expired'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/expired')
            ->where('resumeWindowHours', 24));
});

it('rejects auto-save calls after the resume window with a redirect hint', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    ['questions' => $questions] = glcSeedReading(passages: 1, questionsPerPassage: 1);

    $this->travel(25)->hours();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), [
            'item_id' => $questions[0]->id,
            'selected' => 0,
        ])
        ->assertStatus(410)
        ->assertJsonPath('redirect', route('placement.expired'));
});

it('ignores a cookie whose device token does not match the attempt', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->withCookies([
        PlacementSessionService::COOKIE_NAME => $attempt->id.'|'.str_repeat('x', 64),
    ])
        ->get(route('placement.test'))
        ->assertRedirect(route('placement.entry'));
});

it('accumulates active time on heartbeat and reports remaining seconds', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->travel(20)->seconds();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.heartbeat'))
        ->assertSuccessful()
        ->assertJson([
            'remainingSeconds' => 880,
            'timeUsedSeconds' => 20,
            'sectionCompleted' => false,
        ]);
});

it('does not accumulate idle gaps longer than the inactivity threshold', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->travel(31)->minutes();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.heartbeat'))
        ->assertSuccessful()
        ->assertJson(['timeUsedSeconds' => 0, 'remainingSeconds' => 900]);
});

it('resumes accumulating after an idle pause from the new anchor', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    $cookie = glcPlacementCookie($attempt);

    $this->travel(45)->minutes();
    $this->withCookies($cookie)->postJson(route('placement.heartbeat'))
        ->assertJson(['timeUsedSeconds' => 0]);

    $this->travel(25)->seconds();
    $this->withCookies($cookie)->postJson(route('placement.heartbeat'))
        ->assertJson(['timeUsedSeconds' => 25]);
});

it('force-completes the section and advances when the time limit is reached', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $attempt->sectionStates()
        ->where('section', PlacementSection::Reading)
        ->sole()
        ->update(['time_used_seconds' => 890]);

    $this->travel(15)->seconds();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.heartbeat'))
        ->assertSuccessful()
        ->assertJson([
            'sectionCompleted' => true,
            'redirect' => route('placement.test'),
        ]);

    $attempt->refresh();

    expect($attempt->current_section)->toBe(PlacementSection::GrammarVocabulary)
        ->and($attempt->sectionStates()->where('section', PlacementSection::Reading)->sole()->status)
        ->toBe(PlacementSectionStatus::Completed)
        ->and($attempt->sectionStates()->where('section', PlacementSection::Reading)->sole()->time_used_seconds)
        ->toBe(900)
        ->and($attempt->sectionStates()->where('section', PlacementSection::GrammarVocabulary)->sole()->status)
        ->toBe(PlacementSectionStatus::InProgress);
});

it('rejects answers for items outside the current section', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    $laterQuestions = glcSeedGrammarVocabulary(1);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), [
            'item_id' => $laterQuestions[0]->id,
            'selected' => 0,
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'current section'));
});

it('rejects completing a section that is not the current one', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->withCookies(glcPlacementCookie($attempt))
        ->post(route('placement.section.complete'), ['section' => PlacementSection::Listening->value])
        ->assertSessionHasErrors('section');

    expect($attempt->refresh()->current_section)->toBe(PlacementSection::Reading);
});

it('rejects writing saves while the current section is not writing', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    glcSeedWritingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.writing.save'), ['text' => 'too early'])
        ->assertStatus(409);
});

it('completing the current section unlocks the next in fixed order', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->withCookies(glcPlacementCookie($attempt))
        ->post(route('placement.section.complete'), ['section' => PlacementSection::Reading->value])
        ->assertRedirect(route('placement.test'));

    $attempt->refresh();

    expect($attempt->current_section)->toBe(PlacementSection::GrammarVocabulary)
        ->and($attempt->sectionStates()->where('section', PlacementSection::GrammarVocabulary)->sole()->status)
        ->toBe(PlacementSectionStatus::InProgress)
        ->and($attempt->sectionStates()->where('section', PlacementSection::Listening)->sole()->status)
        ->toBe(PlacementSectionStatus::Locked);
});
