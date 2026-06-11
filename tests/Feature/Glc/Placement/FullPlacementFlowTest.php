<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementAccessCodeStatus;
use App\Enums\Glc\PlacementAttemptStatus;
use App\Enums\Glc\PlacementReviewStatus;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAccessCode;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Models\User;
use Database\Seeders\GlcPlacementContentSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/PlacementTestHelpers.php';

beforeEach(function (): void {
    $this->withoutVite();
    $this->withCredentials();
    $this->withoutMiddleware(ThrottleRequests::class);
    Storage::fake('local');
    $this->seed(GlcPlacementContentSeeder::class);
});

it('runs the full placement flow from admin access code creation through submission', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.access-codes.store'), [
            'quantity' => 1,
            'note' => 'E2E flow test',
        ])
        ->assertRedirectToRoute('admin.access-codes.index');

    $code = PlacementAccessCode::query()->sole();

    expect($code->status)->toBe(PlacementAccessCodeStatus::Unused)
        ->and($code->isUsable())->toBeTrue();

    $this->postJson(route('placement.code.validate'), ['code' => $code->code])
        ->assertSuccessful()
        ->assertJson(['valid' => true]);

    $start = $this->post(route('placement.start'), [
        'code' => $code->code,
        'privacy_acknowledged' => true,
        'name' => 'E2E Candidate',
        'email' => 'e2e-candidate@example.com',
        'age' => 18,
    ]);

    $start->assertRedirect(route('placement.instructions'));

    $attempt = PlacementAttempt::query()->sole();
    $cookie = glcPlacementCookie($attempt);

    $this->withCookies($cookie)
        ->get(route('placement.instructions'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('glc/placement/instructions'));

    $this->withCookies($cookie)
        ->post(route('placement.instructions.acknowledge'), ['acknowledged' => true])
        ->assertRedirect(route('placement.device-check'));

    $this->withCookies($cookie)
        ->post(route('placement.device-check.confirm'), [
            'audio_ok' => true,
            'microphone_ok' => true,
            'recording_ok' => true,
        ])
        ->assertRedirect(route('placement.test'));

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('glc/placement/sections/reading'));

    $readingQuestions = PlacementItem::query()
        ->active()
        ->forSection(PlacementSection::Reading)
        ->whereNotNull('parent_id')
        ->get();

    foreach ($readingQuestions as $question) {
        $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
            'item_id' => $question->id,
            'selected' => 0,
        ])->assertSuccessful();
    }

    $this->withCookies($cookie)
        ->post(route('placement.section.complete'), ['section' => PlacementSection::Reading->value])
        ->assertRedirect(route('placement.test'));

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page->component('glc/placement/sections/grammar-vocabulary'));

    $grammarQuestions = PlacementItem::query()
        ->active()
        ->forSection(PlacementSection::GrammarVocabulary)
        ->whereNull('parent_id')
        ->get();

    foreach ($grammarQuestions as $question) {
        $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
            'item_id' => $question->id,
            'selected' => 0,
        ])->assertSuccessful();
    }

    $this->withCookies($cookie)
        ->post(route('placement.section.complete'), ['section' => PlacementSection::GrammarVocabulary->value])
        ->assertRedirect(route('placement.test'));

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page->component('glc/placement/sections/listening'));

    $clips = PlacementItem::query()
        ->active()
        ->forSection(PlacementSection::Listening)
        ->whereNull('parent_id')
        ->get();

    foreach ($clips as $clip) {
        $this->withCookies($cookie)
            ->postJson(route('placement.listening.play', $clip))
            ->assertSuccessful();
    }

    $listeningQuestions = PlacementItem::query()
        ->active()
        ->forSection(PlacementSection::Listening)
        ->whereNotNull('parent_id')
        ->get();

    foreach ($listeningQuestions as $question) {
        $this->withCookies($cookie)->postJson(route('placement.answers.store'), [
            'item_id' => $question->id,
            'selected' => 0,
        ])->assertSuccessful();
    }

    $this->withCookies($cookie)
        ->post(route('placement.section.complete'), ['section' => PlacementSection::Listening->value])
        ->assertRedirect(route('placement.test'));

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page->component('glc/placement/sections/writing'));

    $this->withCookies($cookie)->postJson(route('placement.writing.save'), [
        'text' => glcEssayText(150),
    ])->assertSuccessful();

    $this->withCookies($cookie)
        ->post(route('placement.section.complete'), ['section' => PlacementSection::Writing->value])
        ->assertRedirect(route('placement.test'));

    $this->withCookies($cookie)
        ->get(route('placement.test'))
        ->assertInertia(fn ($page) => $page->component('glc/placement/sections/speaking'));

    $this->withCookies($cookie)
        ->postJson(route('placement.speaking.upload'), [
            'audio' => UploadedFile::fake()->create('speaking.webm', 128, 'audio/webm'),
            'duration_seconds' => 90,
            'quality_passed' => true,
        ])
        ->assertSuccessful();

    $this->withCookies($cookie)
        ->post(route('placement.submit'))
        ->assertRedirect(route('placement.complete'));

    $attempt->refresh();

    expect($attempt->status)->toBe(PlacementAttemptStatus::Submitted)
        ->and($attempt->submitted_at)->not->toBeNull()
        ->and($attempt->accessCode->status)->toBe(PlacementAccessCodeStatus::Completed)
        ->and($attempt->review()->sole()->status)->toBe(PlacementReviewStatus::Pending);

    $this->withCookies($cookie)
        ->get(route('placement.complete'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('glc/placement/complete')
            ->where('candidateName', 'E2E Candidate'));
});
