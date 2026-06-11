<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementSection;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/PlacementTestHelpers.php';

beforeEach(function (): void {
    $this->withoutVite();
    $this->withCredentials();
});

it('shows listening as the third section with clips and five MCQs per clip', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    glcSeedListening(clips: 2, questionsPerClip: 5);

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.test'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/sections/listening')
            ->where('progress.currentIndex', 3)
            ->has('clips', 2)
            ->has('clips.0.questions', 5)
            ->has('clips.1.questions', 5)
            ->where('clips.0.played', false)
            ->where('timer.timeLimitSeconds', 600));
});

it('allows exactly one play per clip and denies the second request', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);

    $cookie = glcPlacementCookie($attempt);

    $first = $this->withCookies($cookie)
        ->postJson(route('placement.listening.play', $clips[0]))
        ->assertSuccessful()
        ->json();

    expect($first['url'])->toContain('signature=');

    $this->assertDatabaseHas('placement_audio_plays', [
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $clips[0]->id,
    ]);

    $this->withCookies($cookie)
        ->postJson(route('placement.listening.play', $clips[0]))
        ->assertStatus(403)
        ->assertJson(['played' => true])
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'once'));

    expect($attempt->audioPlays()->count())->toBe(1);
});

it('streams the audio through the signed URL after the play is registered', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('glc/placement/audio/clip-1.mp3', 'fake-mp3-bytes');

    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);

    $cookie = glcPlacementCookie($attempt);

    $url = $this->withCookies($cookie)
        ->postJson(route('placement.listening.play', $clips[0]))
        ->json('url');

    $this->withCookies($cookie)
        ->get($url)
        ->assertSuccessful();
});

it('denies streaming without a valid signature', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('glc/placement/audio/clip-1.mp3', 'fake-mp3-bytes');

    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);

    $this->withCookies(glcPlacementCookie($attempt))
        ->get(route('placement.listening.stream', $clips[0]))
        ->assertStatus(403);
});

it('denies streaming to a device without the attempt cookie', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('glc/placement/audio/clip-1.mp3', 'fake-mp3-bytes');

    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);

    $url = $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.listening.play', $clips[0]))
        ->json('url');

    $this->flushHeaders();
    $this->defaultCookies = [];

    $this->get($url)->assertStatus(403);
});

it('marks the clip as played in the page props after playback', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['clips' => $clips] = glcSeedListening(clips: 2, questionsPerClip: 1);

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.listening.play', $clips[0]));

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page
            ->where('clips.0.played', true)
            ->where('clips.1.played', false));
});

it('shows the single-play warning before playback in the listening UI', function (): void {
    $source = file_get_contents(resource_path('js/pages/glc/placement/sections/listening.tsx'));

    expect($source)->toContain('plays only once')
        ->and($source)->toContain('no replay');
});

it('rejects play registration for clips while another section is current', function (): void {
    Storage::fake('local');

    $attempt = glcStartedAttempt(PlacementSection::Reading);
    ['clips' => $clips] = glcSeedListening(clips: 1, questionsPerClip: 1);

    $this->withCookies(glcPlacementCookie($attempt))
        ->postJson(route('placement.listening.play', $clips[0]))
        ->assertStatus(422);

    expect($attempt->audioPlays()->count())->toBe(0);
});

it('saves listening answers and restores them on resume', function (): void {
    $attempt = glcStartedAttempt(PlacementSection::Listening);
    ['questions' => $questions] = glcSeedListening(clips: 1, questionsPerClip: 2);

    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
        'item_id' => $questions[0]->id,
        'selected' => 2,
    ])->assertSuccessful();

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page->where('answers.'.$questions[0]->id, 2));
});
