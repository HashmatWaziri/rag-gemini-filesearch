<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementAccessCodeStatus;
use App\Enums\Glc\PlacementAttemptStatus;
use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Models\Glc\PlacementAccessCode;
use App\Models\Glc\PlacementAttempt;
use App\Services\Glc\Placement\PlacementSessionService;

require_once __DIR__.'/PlacementTestHelpers.php';

beforeEach(function (): void {
    $this->withoutVite();
    $this->withCredentials();
});

function validProfile(PlacementAccessCode $code, array $overrides = []): array
{
    return [
        'code' => $code->code,
        'privacy_acknowledged' => true,
        'name' => 'Aisyah Rahman',
        'email' => 'aisyah@example.com',
        'age' => 21,
        ...$overrides,
    ];
}

it('renders the public entry page without authentication', function (): void {
    $this->get(route('placement.entry'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/entry')
            ->where('minimumAge', 12));
});

it('accepts a valid unused access code', function (): void {
    $code = PlacementAccessCode::factory()->create();

    $this->postJson(route('placement.code.validate'), ['code' => $code->code])
        ->assertSuccessful()
        ->assertJson(['valid' => true]);
});

it('accepts a valid code typed in lowercase', function (): void {
    $code = PlacementAccessCode::factory()->create();

    $this->postJson(route('placement.code.validate'), ['code' => mb_strtolower($code->code)])
        ->assertSuccessful()
        ->assertJson(['valid' => true]);
});

it('rejects an unknown access code with a clear error', function (): void {
    $this->postJson(route('placement.code.validate'), ['code' => 'NOPE1234'])
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'not recognised'));
});

it('rejects a completed (used) access code', function (): void {
    $code = PlacementAccessCode::factory()->completed()->create();

    $this->postJson(route('placement.code.validate'), ['code' => $code->code])
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'already been used'));
});

it('rejects an expired access code', function (): void {
    $code = PlacementAccessCode::factory()->expired()->create();

    $this->postJson(route('placement.code.validate'), ['code' => $code->code])
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'expired'));
});

it('rejects a revoked access code', function (): void {
    $code = PlacementAccessCode::factory()->revoked()->create();

    $this->postJson(route('placement.code.validate'), ['code' => $code->code])
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'revoked'));
});

it('requires an email address to start', function (): void {
    $code = PlacementAccessCode::factory()->create();

    $this->post(route('placement.start'), validProfile($code, ['email' => '']))
        ->assertSessionHasErrors('email');

    expect(PlacementAttempt::query()->count())->toBe(0);
});

it('rejects an invalid email address', function (): void {
    $code = PlacementAccessCode::factory()->create();

    $this->post(route('placement.start'), validProfile($code, ['email' => 'not-an-email']))
        ->assertSessionHasErrors('email');
});

it('requires name, age, and privacy acknowledgment', function (): void {
    $code = PlacementAccessCode::factory()->create();

    $this->post(route('placement.start'), [
        'code' => $code->code,
        'privacy_acknowledged' => false,
        'name' => '',
        'email' => 'someone@example.com',
        'age' => 'abc',
    ])->assertSessionHasErrors(['name', 'age', 'privacy_acknowledged']);
});

it('does not start a session with a used code even with a valid profile', function (): void {
    $code = PlacementAccessCode::factory()->completed()->create();

    $this->post(route('placement.start'), validProfile($code))
        ->assertSessionHasErrors('code');

    expect(PlacementAttempt::query()->count())->toBe(0);
});

it('blocks candidates under the minimum age without creating an attempt', function (): void {
    $code = PlacementAccessCode::factory()->create();

    $this->post(route('placement.start'), validProfile($code, ['age' => 11]))
        ->assertRedirect(route('placement.blocked'));

    expect(PlacementAttempt::query()->count())->toBe(0)
        ->and($code->refresh()->status)->toBe(PlacementAccessCodeStatus::Unused);
});

it('allows a candidate exactly at the minimum age', function (): void {
    $code = PlacementAccessCode::factory()->create();

    $this->post(route('placement.start'), validProfile($code, ['age' => 12]))
        ->assertRedirect(route('placement.instructions'));

    expect(PlacementAttempt::query()->count())->toBe(1);
});

it('renders the blocked screen with contact guidance', function (): void {
    $this->get(route('placement.blocked'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/blocked')
            ->where('minimumAge', 12));
});

it('creates the attempt, section states, and device cookie on successful start', function (): void {
    $code = PlacementAccessCode::factory()->create();

    $response = $this->post(route('placement.start'), validProfile($code));

    $response->assertRedirect(route('placement.instructions'));

    $attempt = PlacementAttempt::query()->sole();

    expect($attempt->status)->toBe(PlacementAttemptStatus::InProgress)
        ->and($attempt->candidate_name)->toBe('Aisyah Rahman')
        ->and($attempt->candidate_email)->toBe('aisyah@example.com')
        ->and($attempt->candidate_age)->toBe(21)
        ->and($attempt->current_section)->toBe(PlacementSection::Reading)
        ->and(mb_strlen($attempt->device_token))->toBe(64)
        ->and($attempt->privacy_acknowledged_at)->not->toBeNull()
        ->and($attempt->started_at)->not->toBeNull()
        ->and($attempt->instructions_acknowledged_at)->toBeNull();

    expect($code->refresh()->status)->toBe(PlacementAccessCodeStatus::InProgress);

    $states = $attempt->sectionStates()->orderBy('id')->get();
    expect($states)->toHaveCount(5)
        ->and($states->pluck('section')->all())->toBe(PlacementSection::ordered())
        ->and($states->pluck('status')->unique()->all())->toBe([PlacementSectionStatus::Locked]);

    expect($states->firstWhere('section', PlacementSection::Reading)->time_limit_seconds)->toBe(900)
        ->and($states->firstWhere('section', PlacementSection::Writing)->time_limit_seconds)->toBe(1500);

    $response->assertCookie(
        PlacementSessionService::COOKIE_NAME,
        $attempt->id.'|'.$attempt->device_token,
    );
});

it('states in the privacy notice that data is not used to train AI models', function (): void {
    $source = file_get_contents(resource_path('js/pages/glc/placement/entry.tsx'));

    expect($source)->toContain('not used to train AI models');
});

it('shows instructions with all five sections, estimated times, and speaking guidance', function (): void {
    $attempt = glcOnboardingAttempt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.instructions'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/instructions')
            ->has('sections', 5)
            ->where('sections.0.label', 'Reading')
            ->where('sections.0.estimatedMinutes', 15)
            ->where('sections.1.label', 'Grammar & Vocabulary')
            ->where('sections.1.estimatedMinutes', 12)
            ->where('sections.2.label', 'Listening')
            ->where('sections.2.estimatedMinutes', 10)
            ->where('sections.3.label', 'Writing')
            ->where('sections.3.estimatedMinutes', 25)
            ->where('sections.4.label', 'Speaking')
            ->where('sections.4.estimatedMinutes', 8));
});

it('includes mobile speaking guidance in the instructions copy', function (): void {
    $source = file_get_contents(resource_path('js/pages/glc/placement/instructions.tsx'));

    expect($source)->toContain('microphone')
        ->and($source)->toContain('phone');
});

it('records the instructions acknowledgment and moves to the device check', function (): void {
    $attempt = glcOnboardingAttempt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->post(route('placement.instructions.acknowledge'), ['acknowledged' => true])
        ->assertRedirect(route('placement.device-check'));

    expect($attempt->refresh()->instructions_acknowledged_at)->not->toBeNull();
});

it('shows the device check after instructions are acknowledged', function (): void {
    $attempt = glcOnboardingAttempt(['instructions_acknowledged_at' => now()]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.device-check'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('glc/placement/device-check'));
});

it('unlocks the reading section when the device check passes', function (): void {
    $attempt = glcOnboardingAttempt(['instructions_acknowledged_at' => now()]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->post(route('placement.device-check.confirm'), [
            'audio_ok' => true,
            'microphone_ok' => true,
            'recording_ok' => true,
        ])
        ->assertRedirect(route('placement.test'));

    $readingState = $attempt->sectionStates()
        ->where('section', PlacementSection::Reading)
        ->sole();

    expect($readingState->status)->toBe(PlacementSectionStatus::InProgress)
        ->and($readingState->started_at)->not->toBeNull();
});

it('rejects the device check confirmation when capabilities are missing', function (): void {
    $attempt = glcOnboardingAttempt(['instructions_acknowledged_at' => now()]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->post(route('placement.device-check.confirm'), [
            'audio_ok' => true,
            'microphone_ok' => false,
            'recording_ok' => true,
        ])
        ->assertSessionHasErrors('microphone_ok');

    expect($attempt->sectionStates()->where('section', PlacementSection::Reading)->sole()->status)
        ->toBe(PlacementSectionStatus::Locked);
});

it('rejects the device check confirmation without a completed test recording', function (): void {
    $attempt = glcOnboardingAttempt(['instructions_acknowledged_at' => now()]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->post(route('placement.device-check.confirm'), [
            'audio_ok' => true,
            'microphone_ok' => true,
        ])
        ->assertSessionHasErrors('recording_ok');

    expect($attempt->sectionStates()->where('section', PlacementSection::Reading)->sole()->status)
        ->toBe(PlacementSectionStatus::Locked);
});

it('redirects the test page back to instructions until they are acknowledged', function (): void {
    $attempt = glcOnboardingAttempt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertRedirect(route('placement.instructions'));
});

it('redirects the test page to the device check until it passes', function (): void {
    $attempt = glcOnboardingAttempt(['instructions_acknowledged_at' => now()]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertRedirect(route('placement.device-check'));
});

it('redirects the entry page to the current step for an in-progress session', function (): void {
    $attempt = glcOnboardingAttempt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.entry'))
        ->assertRedirect(route('placement.instructions'));
});
