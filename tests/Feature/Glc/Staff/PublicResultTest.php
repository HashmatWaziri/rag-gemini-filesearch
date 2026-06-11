<?php

declare(strict_types=1);

use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementResultLink;
use App\Models\Glc\PlacementReview;
use App\Models\Glc\PlacementScore;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
});

function sentResultLink(): PlacementResultLink
{
    $attempt = PlacementAttempt::factory()->submitted()->create([
        'candidate_name' => 'Aisha Rahman',
        'candidate_age' => 25,
    ]);

    PlacementReview::factory()->approved()->create([
        'placement_attempt_id' => $attempt->id,
        'status' => 'sent',
    ]);

    PlacementScore::factory()->create(['placement_attempt_id' => $attempt->id]);

    return PlacementResultLink::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'email_to' => $attempt->candidate_email,
    ]);
}

it('shows the result page for a valid unexpired token without any login', function (): void {
    $link = sentResultLink();

    $this->get(route('placement.result.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('glc/staff/result-public')
            ->where('candidateName', 'Aisha Rahman')
            ->where('overallLevel', 'Intermediate')
            ->has('skillLevels', 5)
            ->where('skillLevels.0.skill', 'Reading')
            ->where('narrative.strengths', 'Reads confidently and uses a fair range of vocabulary.')
        );

    expect($link->refresh()->last_viewed_at)->not->toBeNull();
});

it('never leaks internal notes, AI confidence, percentages, or override data publicly', function (): void {
    $link = sentResultLink();
    $review = $link->attempt->review;

    $review->update(['override_reason' => 'INTERNAL-OVERRIDE-REASON']);
    $review->notes()->create([
        'author_id' => User::factory()->teacher()->create()->id,
        'note' => 'SECRET-INTERNAL-NOTE',
    ]);

    $content = $this->get(route('placement.result.show', $link->token))->getContent();

    expect($content)
        ->not->toContain('SECRET-INTERNAL-NOTE')
        ->not->toContain('INTERNAL-OVERRIDE-REASON')
        ->not->toContain('confidence')
        ->not->toContain('override_reason')
        ->not->toContain('66.67')
        ->not->toContain('63.88')
        ->not->toContain('section_scores');
});

it('downloads the PDF with correct headers within the validity window', function (): void {
    $link = sentResultLink();

    $response = $this->get(route('placement.result.download', $link->token));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))
        ->toContain('attachment')
        ->toContain('placement-test-result.pdf');
});

it('shows a friendly contact-GLC page for expired links', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();
    PlacementReview::factory()->approved()->create([
        'placement_attempt_id' => $attempt->id,
        'status' => 'sent',
    ]);
    $link = PlacementResultLink::factory()->expired()->create([
        'placement_attempt_id' => $attempt->id,
    ]);

    $this->get(route('placement.result.show', $link->token))
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('glc/staff/result-expired'));

    $this->get(route('placement.result.download', $link->token))
        ->assertNotFound();
});

it('shows the expired page for unknown tokens', function (): void {
    $this->get(route('placement.result.show', 'unknown-token-value'))
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('glc/staff/result-expired'));
});

it('does not expose results whose review is no longer releasable', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create();
    PlacementReview::factory()->create(['placement_attempt_id' => $attempt->id]);
    $link = PlacementResultLink::factory()->create(['placement_attempt_id' => $attempt->id]);

    $this->get(route('placement.result.show', $link->token))
        ->assertNotFound();
});

it('keeps candidate-facing routes free of staff data even for authenticated staff', function (): void {
    $link = sentResultLink();

    actingAs(User::factory()->admin()->create())
        ->get(route('placement.result.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('score')
            ->missing('ai_drafts')
            ->missing('ai_recommendation')
            ->missing('notes')
        );
});
