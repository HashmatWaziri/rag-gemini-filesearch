<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementSection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/PlacementTestHelpers.php';

beforeEach(function (): void {
    $this->withoutVite();
    $this->withCredentials();
});

function speakingUpload(array $overrides = []): array
{
    return [
        'audio' => UploadedFile::fake()->create('speaking-response.webm', 256, 'audio/webm'),
        'duration_seconds' => 95,
        'quality_passed' => true,
        ...$overrides,
    ];
}

it('shows speaking as the fifth section with prompt and attempt status', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Speaking);
    glcSeedSpeakingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/sections/speaking')
            ->where('progress.currentIndex', 5)
            ->where('prompt.maxDurationSeconds', 180)
            ->where('prompt.maxAttempts', 3)
            ->where('recording.attemptsUsed', 0)
            ->where('recording.hasRecording', false)
            ->where('timer.timeLimitSeconds', 480));
});

it('stores a quality-passing recording and counts the attempt', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Speaking);
    $prompt = glcSeedSpeakingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.speaking.upload'), speakingUpload())
        ->assertSuccessful()
        ->assertJson([
            'counted' => true,
            'attemptsUsed' => 1,
            'attemptsRemaining' => 2,
        ]);

    $answer = $attempt->answers()->where('placement_item_id', $prompt->id)->sole();

    expect($answer->recording_attempts)->toBe(1)
        ->and($answer->response['duration_seconds'])->toBe(95)
        ->and($answer->response['audio_path'])->toStartWith('glc/placement/recordings/'.$attempt->id);

    Storage::disk('local')->assertExists($answer->response['audio_path']);
});

it('does not count a failed quality check toward the attempt limit', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Speaking);
    $prompt = glcSeedSpeakingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.speaking.upload'), [
            'quality_passed' => false,
            'duration_seconds' => 12,
        ])
        ->assertSuccessful()
        ->assertJson([
            'counted' => false,
            'attemptsUsed' => 0,
            'attemptsRemaining' => 3,
        ])
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'does not count'));

    expect($attempt->answers()->where('placement_item_id', $prompt->id)->exists())->toBeFalse();
});

it('still allows three valid attempts after repeated quality failures', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Speaking);
    glcSeedSpeakingPrompt();

    $cookie = glcPlacementCookie($attempt);

    foreach (range(1, 2) as $i) {
        $this->withCookies($cookie)->postJson(route('placement.speaking.upload'), [
            'quality_passed' => false,
            'duration_seconds' => 10,
        ])->assertSuccessful()->assertJson(['counted' => false]);
    }

    foreach ([1, 2, 3] as $expectedAttempts) {
        $this->withCookies($cookie)
            ->postJson(route('placement.speaking.upload'), speakingUpload())
            ->assertSuccessful()
            ->assertJson(['counted' => true, 'attemptsUsed' => $expectedAttempts]);
    }
});

it('rejects a fourth quality-passing upload', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Speaking);
    glcSeedSpeakingPrompt();

    $cookie = glcPlacementCookie($attempt);

    foreach (range(1, 3) as $i) {
        $this->withCookies($cookie)
            ->postJson(route('placement.speaking.upload'), speakingUpload())
            ->assertSuccessful();
    }

    $this->withCookies($cookie)
        ->postJson(route('placement.speaking.upload'), speakingUpload())
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'all 3 recording attempts'));
});

it('replaces the stored recording on a later valid attempt', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Speaking);
    $prompt = glcSeedSpeakingPrompt();

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.speaking.upload'), speakingUpload([
        'duration_seconds' => 60,
    ]))->assertSuccessful();

    $this->withCookies($cookie)->postJson(route('placement.speaking.upload'), speakingUpload([
        'duration_seconds' => 120,
    ]))->assertSuccessful();

    $answer = $attempt->answers()->where('placement_item_id', $prompt->id)->sole();

    expect($answer->recording_attempts)->toBe(2)
        ->and($answer->response['duration_seconds'])->toBe(120);
});

it('rejects recordings longer than the allowed duration', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Speaking);
    glcSeedSpeakingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.speaking.upload'), speakingUpload([
            'duration_seconds' => 400,
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('duration_seconds');
});

it('rejects non-audio uploads', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Speaking);
    glcSeedSpeakingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.speaking.upload'), speakingUpload([
            'audio' => UploadedFile::fake()->create('payload.pdf', 64, 'application/pdf'),
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('audio');
});

it('accepts ogg and mp4 containers as well', function (string $name, string $mime): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Speaking);
    glcSeedSpeakingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.speaking.upload'), speakingUpload([
            'audio' => UploadedFile::fake()->create($name, 128, $mime),
        ]))
        ->assertSuccessful()
        ->assertJson(['counted' => true]);
})->with([
    ['speaking.ogg', 'audio/ogg'],
    ['speaking.m4a', 'audio/mp4'],
]);

it('rejects uploads while speaking is not the current section', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Writing);
    glcSeedSpeakingPrompt();

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.speaking.upload'), speakingUpload())
        ->assertStatus(409);
});

it('gives clear retry messages for silent, quiet, and distorted recordings', function (): void {
    $source = file_get_contents(resource_path('js/pages/glc/placement/lib/recording-quality.ts'));

    expect($source)->toContain('silent')
        ->and($source)->toContain('too quiet')
        ->and($source)->toContain('distorted')
        ->and($source)->toContain('does not count');
});

it('shows a blocking message with guidance when the microphone is missing', function (): void {
    $source = file_get_contents(resource_path('js/pages/glc/placement/sections/speaking.tsx'));

    expect($source)->toContain('Microphone access was blocked or no microphone was found');
});
