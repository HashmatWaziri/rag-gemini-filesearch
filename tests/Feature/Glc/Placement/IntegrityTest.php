<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementAccessCodeStatus;
use App\Enums\Glc\PlacementAttemptStatus;
use App\Enums\Glc\PlacementIntegrityEventType;
use App\Enums\Glc\PlacementSection;

require_once __DIR__.'/PlacementTestHelpers.php';

beforeEach(function (): void {
    $this->withoutVite();
    $this->withCredentials();
});

it('records a tab-switch integrity event', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.integrity.store'), [
            'type' => PlacementIntegrityEventType::TabSwitch->value,
        ])
        ->assertSuccessful()
        ->assertJson(['recorded' => true]);

    $event = $attempt->integrityEvents()->sole();

    expect($event->type)->toBe(PlacementIntegrityEventType::TabSwitch)
        ->and($event->occurred_at)->not->toBeNull();
});

it('records each tab switch separately for staff visibility', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    $cookie = glcPlacementCookie($attempt);

    foreach (range(1, 2) as $i) {
        $this->withCookies($cookie)->postJson(route('placement.integrity.store'), [
            'type' => PlacementIntegrityEventType::TabSwitch->value,
        ])->assertSuccessful();
    }

    expect($attempt->integrityEvents()->where('type', PlacementIntegrityEventType::TabSwitch)->count())
        ->toBe(2);
});

it('warns the candidate in the UI when the tab is switched', function (): void {
    $source = file_get_contents(resource_path('js/pages/glc/placement/components/section-shell.tsx'));

    expect($source)->toContain('visibilitychange')
        ->and($source)->toContain('tab_switch')
        ->and($source)->toContain('recorded');
});

it('rejects client attempts to record a dual-device event directly', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.integrity.store'), [
            'type' => PlacementIntegrityEventType::DualDevice->value,
        ])
        ->assertStatus(422);

    expect($attempt->integrityEvents()->count())->toBe(0);
});

it('terminates the attempt when a second device presents the in-progress code', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::GrammarVocabulary);

    $this->postJson(route('placement.code.validate'), [
        'code' => $attempt->accessCode->code,
    ])
        ->assertStatus(409)
        ->assertJsonPath('redirect', route('placement.terminated'))
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'another device'));

    $attempt->refresh();

    expect($attempt->status)->toBe(PlacementAttemptStatus::Terminated)
        ->and($attempt->terminated_at)->not->toBeNull()
        ->and($attempt->termination_reason)->toBe('dual_device');

    $event = $attempt->integrityEvents()->sole();
    expect($event->type)->toBe(PlacementIntegrityEventType::DualDevice);

    expect($attempt->accessCode->refresh()->status)->toBe(PlacementAccessCodeStatus::InProgress);
});

it('terminates on dual-device use through the start endpoint as well', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->post(route('placement.start'), [
        'code' => $attempt->accessCode->code,
        'privacy_acknowledged' => true,
        'name' => 'Second Device',
        'email' => 'second@example.com',
        'age' => 30,
    ])->assertRedirect(route('placement.terminated'));

    expect($attempt->refresh()->status)->toBe(PlacementAttemptStatus::Terminated)
        ->and($attempt->integrityEvents()->where('type', PlacementIntegrityEventType::DualDevice)->count())->toBe(1);
});

it('shows the termination screen to the original device after termination', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->postJson(route('placement.code.validate'), ['code' => $attempt->accessCode->code])
        ->assertStatus(409);

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertRedirect(route('placement.terminated'));

    $this->get(route('placement.terminated'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('glc/placement/terminated'));
});

it('blocks saves from the terminated session with a redirect hint', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);
    ['questions' => $questions] = glcSeedReading(passages: 1, questionsPerPassage: 1);

    $this->postJson(route('placement.code.validate'), ['code' => $attempt->accessCode->code])
        ->assertStatus(409);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.answers.store'), [
            'item_id' => $questions[0]->id,
            'selected' => 0,
        ])
        ->assertStatus(409)
        ->assertJsonPath('redirect', route('placement.terminated'));
});

it('resumes instead of terminating when the same device re-enters its code', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.code.validate'), ['code' => $attempt->accessCode->code])
        ->assertSuccessful()
        ->assertJson(['valid' => true, 'resume' => true])
        ->assertJsonPath('redirect', route('placement.test'));

    expect($attempt->refresh()->status)->toBe(PlacementAttemptStatus::InProgress)
        ->and($attempt->integrityEvents()->count())->toBe(0);
});

it('does not allow a terminated code to start a new session', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->postJson(route('placement.code.validate'), ['code' => $attempt->accessCode->code])
        ->assertStatus(409);

    $this->postJson(route('placement.code.validate'), ['code' => $attempt->accessCode->code])
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'already been used'));
});
