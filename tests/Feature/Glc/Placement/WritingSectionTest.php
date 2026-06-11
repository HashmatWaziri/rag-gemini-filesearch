<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementIntegrityEventType;
use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;

require_once __DIR__.'/PlacementTestHelpers.php';

beforeEach(function (): void {
    $this->withoutVite();
    $this->withCredentials();
});

it('shows writing as the fourth section with the prompt and saved text', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Writing);
    glcSeedWritingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/sections/writing')
            ->where('progress.currentIndex', 4)
            ->where('prompt.minWords', 150)
            ->where('prompt.maxWords', 250)
            ->where('saved.text', '')
            ->where('saved.wordCount', 0)
            ->where('config.autosaveIntervalSeconds', 5)
            ->where('timer.timeLimitSeconds', 1500));
});

it('cannot finish the writing section below 150 words', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Writing);
    glcSeedWritingPrompt();

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.writing.save'), [
        'text' => glcEssayText(149),
    ])->assertSuccessful();

    $this->withCookies($cookie)
        ->post(route('placement.section.complete'), ['section' => PlacementSection::Writing->value])
        ->assertSessionHasErrors('words');

    expect($attempt->refresh()->current_section)->toBe(PlacementSection::Writing);
});

it('cannot finish the writing section with no saved essay', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Writing);
    glcSeedWritingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->post(route('placement.section.complete'), ['section' => PlacementSection::Writing->value])
        ->assertSessionHasErrors('words');
});

it('finishes the writing section at 150 words or more', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Writing);
    glcSeedWritingPrompt();

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.writing.save'), [
        'text' => glcEssayText(150),
    ])->assertSuccessful();

    $this->withCookies($cookie)
        ->post(route('placement.section.complete'), ['section' => PlacementSection::Writing->value])
        ->assertRedirect(route('placement.test'));

    expect($attempt->refresh()->current_section)->toBe(PlacementSection::Speaking);
});

it('allows finishing the writing section above 250 words (soft warning only)', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Writing);
    glcSeedWritingPrompt();

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.writing.save'), [
        'text' => glcEssayText(300),
    ])->assertSuccessful()->assertJson(['wordCount' => 300]);

    $this->withCookies($cookie)
        ->post(route('placement.section.complete'), ['section' => PlacementSection::Writing->value])
        ->assertRedirect(route('placement.test'));

    expect($attempt->refresh()->current_section)->toBe(PlacementSection::Speaking);
});

it('shows a soft warning in the writing UI above the maximum word count', function (): void {
    $source = file_get_contents(resource_path('js/pages/glc/placement/sections/writing.tsx'));

    expect($source)->toContain('you can still submit')
        ->and($source)->toContain('aboveMax');
});

it('blocks paste in the writing area client-side', function (): void {
    $source = file_get_contents(resource_path('js/pages/glc/placement/sections/writing.tsx'));

    expect($source)->toContain('onPaste')
        ->and($source)->toContain('preventDefault');
});

it('records a paste attempt as an integrity event', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Writing);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.integrity.store'), [
            'type' => PlacementIntegrityEventType::PasteAttempt->value,
            'context' => 'writing_textarea',
        ])
        ->assertSuccessful()
        ->assertJson(['recorded' => true]);

    $event = $attempt->integrityEvents()->sole();

    expect($event->type)->toBe(PlacementIntegrityEventType::PasteAttempt)
        ->and($event->metadata)->toBe(['context' => 'writing_textarea'])
        ->and($event->occurred_at)->not->toBeNull();
});

it('records every repeated paste attempt', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Writing);
    $cookie = glcPlacementCookie($attempt);

    foreach (range(1, 3) as $i) {
        $this->withCookies($cookie)->postJson(route('placement.integrity.store'), [
            'type' => PlacementIntegrityEventType::PasteAttempt->value,
        ])->assertSuccessful();
    }

    expect($attempt->integrityEvents()->count())->toBe(3);
});

it('restores the saved essay when resuming the writing section', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Writing);
    glcSeedWritingPrompt();

    $cookie = glcPlacementCookie($attempt);
    $essay = glcEssayText(160);

    $this->withCookies($cookie)->postJson(route('placement.writing.save'), ['text' => $essay]);

    $this->travel(3)->hours();

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page
            ->where('saved.text', $essay)
            ->where('saved.wordCount', 160));
});

it('force-completes writing on timeout even below the minimum words', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Writing);
    glcSeedWritingPrompt();

    $attempt->sectionStates()
        ->where('section', PlacementSection::Writing)
        ->sole()
        ->update(['time_used_seconds' => 1499]);

    $this->travel(10)->seconds();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.heartbeat'))
        ->assertJson(['sectionCompleted' => true]);

    expect($attempt->refresh()->current_section)->toBe(PlacementSection::Speaking)
        ->and($attempt->sectionStates()->where('section', PlacementSection::Writing)->sole()->status)
        ->toBe(PlacementSectionStatus::Completed);
});
