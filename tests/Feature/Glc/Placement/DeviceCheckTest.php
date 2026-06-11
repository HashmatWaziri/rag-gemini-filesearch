<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementSection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/PlacementTestHelpers.php';

beforeEach(function (): void {
    $this->withoutVite();
    $this->withCredentials();
});

function micCheckUpload(array $overrides = []): array
{
    return [
        'audio' => UploadedFile::fake()->create('mic-check.webm', 128, 'audio/webm'),
        'duration_seconds' => 8,
        ...$overrides,
    ];
}

function fakeGeminiTranscription(string $transcript): void
{
    config(['gemini.api_key' => 'test-key']);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode(['transcript' => $transcript]),
                ]]],
            ]],
        ]),
    ]);
}

it('shows the audio setup wizard with the recording limit', function (): void {
    $attempt = glcOnboardingAttempt(['instructions_acknowledged_at' => now()]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.device-check'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/device-check')
            ->where('recordingMaxSeconds', 10));
});

it('transcribes the mic-check recording and returns what was heard', function (): void {
    fakeGeminiTranscription('Hello, my name is Sara and I am testing my microphone.');

    $attempt = glcOnboardingAttempt(['instructions_acknowledged_at' => now()]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.device-check.transcribe'), micCheckUpload())
        ->assertSuccessful()
        ->assertJson([
            'transcript' => 'Hello, my name is Sara and I am testing my microphone.',
            'transcriptionAvailable' => true,
        ]);
});

it('never stores the mic-check recording', function (): void {
    Storage::fake('local');
    fakeGeminiTranscription('Testing one two three.');

    $attempt = glcOnboardingAttempt(['instructions_acknowledged_at' => now()]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.device-check.transcribe'), micCheckUpload())
        ->assertSuccessful();

    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('degrades gracefully when no Gemini key is configured', function (): void {
    config(['gemini.api_key' => '']);
    Http::fake();

    $attempt = glcOnboardingAttempt(['instructions_acknowledged_at' => now()]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.device-check.transcribe'), micCheckUpload())
        ->assertSuccessful()
        ->assertJson([
            'transcript' => null,
            'transcriptionAvailable' => false,
        ]);

    Http::assertNothingSent();
});

it('degrades gracefully when the Gemini request fails', function (): void {
    config(['gemini.api_key' => 'test-key']);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([], 500),
    ]);

    $attempt = glcOnboardingAttempt(['instructions_acknowledged_at' => now()]);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.device-check.transcribe'), micCheckUpload())
        ->assertSuccessful()
        ->assertJson([
            'transcript' => null,
            'transcriptionAvailable' => false,
        ]);
});

it('validates the mic-check upload', function (): void {
    fakeGeminiTranscription('unused');

    $attempt = glcOnboardingAttempt(['instructions_acknowledged_at' => now()]);
    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)
        ->postJson(route('placement.device-check.transcribe'), ['duration_seconds' => 5])
        ->assertStatus(422)
        ->assertJsonValidationErrors('audio');

    $this->withCookies($cookie)
        ->postJson(route('placement.device-check.transcribe'), micCheckUpload([
            'duration_seconds' => 60,
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('duration_seconds');

    $this->withCookies($cookie)
        ->postJson(route('placement.device-check.transcribe'), micCheckUpload([
            'audio' => UploadedFile::fake()->create('payload.pdf', 64, 'application/pdf'),
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('audio');
});

it('rejects the mic check once the test has started', function (): void {
    fakeGeminiTranscription('unused');

    $attempt = glcStartedAttempt(PlacementSection::Reading);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.device-check.transcribe'), micCheckUpload())
        ->assertStatus(409);
});

it('rejects the mic check without an active session', function (): void {
    $this->postJson(route('placement.device-check.transcribe'), micCheckUpload())
        ->assertStatus(401);
});

it('exposes the listening auto-start delay to test sections', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    glcSeedListening(clips: 1, questionsPerClip: 1);

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page
            ->where('config.listeningAutoStartSeconds', 10));
});
