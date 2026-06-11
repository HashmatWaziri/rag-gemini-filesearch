<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementReviewStatus;
use App\Models\Glc\AuditLog;
use App\Models\Glc\PlacementReview;
use App\Models\Glc\PlacementScore;
use App\Models\User;
use App\Services\Glc\Review\ResultPdfRenderer;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
});

/**
 * @return array<string, string>
 */
function narrativePayload(): array
{
    return [
        'strengths' => 'Reads confidently and uses a fair range of vocabulary.',
        'areas_to_improve' => 'Listening accuracy needs reinforcement.',
        'recommendation' => 'Place in Intermediate.',
        'next_steps' => 'Enroll in the Intermediate course.',
    ];
}

it('saves the four structured narrative fields without approving them', function (): void {
    $review = PlacementReview::factory()->create(['status' => PlacementReviewStatus::InReview]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->put(route('staff.review.narrative.update', $review), narrativePayload())
        ->assertRedirect();

    $review->refresh();

    expect($review->narrative)->toBe(narrativePayload())
        ->and($review->narrative_approved_at)->toBeNull();
});

it('invalidates a previous narrative approval when the text is edited', function (): void {
    $review = PlacementReview::factory()->approved()->create();
    expect($review->isNarrativeApproved())->toBeTrue();

    actingAs(User::factory()->academicSupervisor()->create())
        ->put(route('staff.review.narrative.update', $review), narrativePayload())
        ->assertRedirect();

    expect($review->refresh()->narrative_approved_at)->toBeNull()
        ->and($review->canGeneratePdf())->toBeFalse();
});

it('requires all four fields before the narrative can be approved', function (): void {
    $review = PlacementReview::factory()->create([
        'status' => PlacementReviewStatus::InReview,
        'narrative' => ['strengths' => 'Good reader.', 'areas_to_improve' => null, 'recommendation' => null, 'next_steps' => null],
    ]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->post(route('staff.review.narrative.approve', $review))
        ->assertSessionHasErrors('narrative');

    expect($review->refresh()->narrative_approved_at)->toBeNull();
});

it('approves the narrative explicitly with audit trail', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $review = PlacementReview::factory()->create([
        'status' => PlacementReviewStatus::InReview,
        'narrative' => narrativePayload(),
    ]);

    actingAs($supervisor)
        ->post(route('staff.review.narrative.approve', $review))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $review->refresh();

    expect($review->narrative_approved_at)->not->toBeNull()
        ->and($review->narrative_approved_by)->toBe($supervisor->id)
        ->and(AuditLog::query()->where('action', AuditAction::NarrativeApproved)->exists())->toBeTrue();
});

it('prefills the editor from the staff-only AI narrative draft helper', function (): void {
    config(['gemini.api_key' => 'test-key']);
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => json_encode([
                'strengths' => 'Strong reading comprehension.',
                'areas_to_improve' => 'Needs listening practice.',
                'recommendation' => 'Intermediate placement.',
                'next_steps' => 'Join the Intermediate course.',
            ])]]]]],
        ]),
    ]);

    $review = PlacementReview::factory()->create(['status' => PlacementReviewStatus::InReview]);

    $response = actingAs(User::factory()->academicSupervisor()->create())
        ->post(route('staff.review.narrative.draft', $review));

    $response->assertOk();
    expect($response->json('narrative.strengths'))->toBe('Strong reading comprehension.');

    expect($review->refresh()->narrative)->toBeNull()
        ->and($review->narrative_approved_at)->toBeNull();
});

it('tolerates AI narrative draft failure with a clear jargon-free message', function (): void {
    config(['gemini.api_key' => null]);
    Http::fake();

    $review = PlacementReview::factory()->create(['status' => PlacementReviewStatus::InReview]);

    $response = actingAs(User::factory()->academicSupervisor()->create())
        ->post(route('staff.review.narrative.draft', $review));

    $response->assertStatus(422);
    expect($response->json('message'))
        ->toContain('write the parent summary yourself')
        ->not->toContain('GEMINI');
});

it('blocks PDF generation until narrative AND review are approved', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();

    $review = PlacementReview::factory()->approved()->create(['narrative_approved_at' => null]);
    actingAs($supervisor)->get(route('staff.review.pdf', $review))->assertForbidden();

    $other = PlacementReview::factory()->create([
        'status' => PlacementReviewStatus::InReview,
        'narrative' => narrativePayload(),
        'narrative_approved_at' => now(),
        'final_level' => GlcLevel::Intermediate,
    ]);
    actingAs($supervisor)->get(route('staff.review.pdf', $other))->assertForbidden();
});

it('streams the PDF to staff once fully approved', function (): void {
    $review = PlacementReview::factory()->approved()->create();

    actingAs(User::factory()->teacher()->create())
        ->get(route('staff.review.pdf', $review))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('renders per-skill levels and overall level on the PDF without internal data', function (): void {
    $review = PlacementReview::factory()->approved()->create();
    $review->notes()->create(['author_id' => null, 'note' => 'SECRET-INTERNAL-NOTE']);
    PlacementScore::factory()->create([
        'placement_attempt_id' => $review->placement_attempt_id,
        'composite' => 63.88,
    ]);

    $data = app(ResultPdfRenderer::class)->viewData($review->refresh());
    $html = view('glc.placement-result-pdf', $data)->render();

    expect($html)
        ->toContain('Placement Test Result')
        ->toContain($review->attempt->candidate_name)
        ->toContain('Intermediate')
        ->toContain('Reading')
        ->toContain('Grammar &amp; Vocabulary')
        ->toContain('Listening')
        ->toContain('Writing')
        ->toContain('Speaking')
        ->toContain('Reads confidently')
        ->not->toContain('SECRET-INTERNAL-NOTE')
        ->not->toContain('confidence')
        ->not->toContain('63.88')
        ->not->toContain('66.67')
        ->not->toContain('signature');
});
