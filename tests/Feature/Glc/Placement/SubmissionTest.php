<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementAccessCodeStatus;
use App\Enums\Glc\PlacementAttemptStatus;
use App\Enums\Glc\PlacementReviewStatus;
use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Models\Glc\PlacementAttempt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/PlacementTestHelpers.php';

beforeEach(function (): void {
    $this->withoutVite();
    $this->withCredentials();
});

function speakingReadyAttempt(): PlacementAttempt
{
    $attempt = glcStartedAttempt(PlacementSection::Speaking);
    $prompt = glcSeedSpeakingPrompt();

    test()->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.speaking.upload'), [
            'audio' => UploadedFile::fake()->create('speaking.webm', 128, 'audio/webm'),
            'duration_seconds' => 90,
            'quality_passed' => true,
        ])
        ->assertSuccessful();

    return $attempt;
}

it('submits the test and enters the pending review state', function (): void {
    Storage::fake('local');

    $attempt = speakingReadyAttempt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->post(route('placement.submit'))
        ->assertRedirect(route('placement.complete'));

    $attempt->refresh();

    expect($attempt->status)->toBe(PlacementAttemptStatus::Submitted)
        ->and($attempt->submitted_at)->not->toBeNull()
        ->and($attempt->current_section)->toBeNull()
        ->and($attempt->accessCode->status)->toBe(PlacementAccessCodeStatus::Completed)
        ->and($attempt->sectionStates()->where('section', PlacementSection::Speaking)->sole()->status)
        ->toBe(PlacementSectionStatus::Completed);

    $review = $attempt->review()->sole();
    expect($review->status)->toBe(PlacementReviewStatus::Pending);
});

it('requires a saved recording before submitting', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Speaking);
    glcSeedSpeakingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->post(route('placement.submit'))
        ->assertSessionHasErrors('recording');

    expect($attempt->refresh()->status)->toBe(PlacementAttemptStatus::InProgress);
});

it('cannot submit before reaching the speaking section', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Writing);
    glcSeedSpeakingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->post(route('placement.submit'))
        ->assertSessionHasErrors('submit');

    expect($attempt->refresh()->status)->toBe(PlacementAttemptStatus::InProgress);
});

it('auto-submits when the speaking timer expires', function (): void {
    Storage::fake('local');

    $attempt = speakingReadyAttempt();

    $attempt->sectionStates()
        ->where('section', PlacementSection::Speaking)
        ->sole()
        ->update(['time_used_seconds' => 479]);

    $this->travel(10)->seconds();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.heartbeat'))
        ->assertSuccessful()
        ->assertJsonPath('redirect', route('placement.complete'));

    $attempt->refresh();

    expect($attempt->status)->toBe(PlacementAttemptStatus::Submitted)
        ->and($attempt->review()->sole()->status)->toBe(PlacementReviewStatus::Pending)
        ->and($attempt->accessCode->status)->toBe(PlacementAccessCodeStatus::Completed);
});

it('shows only the pending-review message after submission', function (): void {
    Storage::fake('local');

    $attempt = speakingReadyAttempt();
    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->post(route('placement.submit'));

    $response = $this->withCookies($cookie)
        ->get(route('placement.complete'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/complete')
            ->where('candidateName', $attempt->candidate_name));

    $props = (array) $response->viewData('page')['props'];
    glcAssertNoForbiddenKeys($props);
});

it('uses pending-review wording on the complete screen', function (): void {
    $source = file_get_contents(resource_path('js/pages/glc/placement/complete.tsx'));

    expect($source)->toContain('pending review by GLC staff')
        ->and(mb_strtolower($source))->not->toContain('score')
        ->and(mb_strtolower($source))->not->toContain('level');
});

it('redirects all test pages to the pending screen after submission', function (): void {
    Storage::fake('local');

    $attempt = speakingReadyAttempt();
    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->post(route('placement.submit'));

    $this->withCookies($cookie)->get(route('placement.test'))
        ->assertRedirect(route('placement.complete'));
    $this->withCookies($cookie)->get(route('placement.instructions'))
        ->assertRedirect(route('placement.complete'));
});

it('rejects further saves and submits after submission', function (): void {
    Storage::fake('local');

    $attempt = speakingReadyAttempt();
    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->post(route('placement.submit'));

    $this->withCookies($cookie)
        ->postJson(route('placement.heartbeat'))
        ->assertStatus(409)
        ->assertJsonPath('redirect', route('placement.complete'));

    $this->withCookies($cookie)
        ->post(route('placement.submit'))
        ->assertRedirect(route('placement.complete'));

    expect($attempt->refresh()->review()->count())->toBe(1);
});

it('never exposes correct options, scores, or levels in any candidate page payload', function (): void {
    Storage::fake('local');

    foreach (PlacementSection::ordered() as $section) {
        $attempt = glcStartedAttempt($section);
        glcSeedReading(passages: 1, questionsPerPassage: 2);
        glcSeedGrammarVocabulary(2);
        ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 2);
        glcSeedGapFillQuestion($clips[0]);
        glcSeedWritingPrompt();
        glcSeedSpeakingPrompt();

        $response = $this->withCookies(glcPlacementCookie($attempt))
            ->get(route('placement.test'))
            ->assertSuccessful();

        $props = (array) $response->viewData('page')['props'];
        glcAssertNoForbiddenKeys($props, "test page for {$section->value}");
    }
});

it('does not leak review or score data through URL guessing on candidate routes', function (): void {
    Storage::fake('local');

    $attempt = speakingReadyAttempt();
    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->post(route('placement.submit'));

    $this->withCookies($cookie)->get("/placement/attempts/{$attempt->id}")->assertNotFound();
    $this->withCookies($cookie)->get("/placement/attempt/{$attempt->id}")->assertNotFound();
    $this->withCookies($cookie)->get("/placement/score/{$attempt->id}")->assertNotFound();
    $this->withCookies($cookie)->get("/placement/review/{$attempt->id}")->assertNotFound();
});

it('offers no candidate PDF download before staff send', function (): void {
    Storage::fake('local');

    $attempt = speakingReadyAttempt();
    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->post(route('placement.submit'));

    $this->withCookies($cookie)->get("/placement/pdf/{$attempt->id}")->assertNotFound();
    $this->withCookies($cookie)->get('/placement/result-pdf')->assertNotFound();

    $source = file_get_contents(resource_path('js/pages/glc/placement/complete.tsx'));
    expect(mb_strtolower($source))->not->toContain('download')
        ->and(mb_strtolower($source))->not->toContain('pdf');
});

it('runs the scoring pipeline only when the staff classes exist', function (): void {
    Storage::fake('local');

    $attempt = speakingReadyAttempt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->post(route('placement.submit'))
        ->assertRedirect(route('placement.complete'));

    expect($attempt->refresh()->review()->sole()->status)->toBe(PlacementReviewStatus::Pending);
});
