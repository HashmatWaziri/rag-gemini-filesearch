<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementReviewStatus;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\AuditLog;
use App\Models\Glc\PlacementAiDraft;
use App\Models\Glc\PlacementAiRecommendation;
use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementIntegrityEvent;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementReview;
use App\Models\Glc\PlacementScore;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
});

function intermediateScore(PlacementReview $review): PlacementScore
{
    return PlacementScore::factory()->create([
        'placement_attempt_id' => $review->placement_attempt_id,
        'section_scores' => [
            'reading' => 66.67,
            'grammar_vocabulary' => 72.73,
            'listening' => 60.0,
            'writing' => 64.0,
            'speaking' => 60.0,
        ],
        'composite' => 64.68,
        'suggested_level' => GlcLevel::Intermediate,
    ]);
}

/**
 * @return array<string, mixed>
 */
function decisionPayload(string $final = 'intermediate', array $overrides = []): array
{
    return [
        'final_level' => $final,
        'skill_levels' => array_merge([
            'reading' => 'intermediate',
            'grammar_vocabulary' => 'intermediate',
            'listening' => 'intermediate',
            'writing' => 'intermediate',
            'speaking' => 'intermediate',
        ], $overrides),
    ];
}

it('shows the review detail with drafts, integrity events, and scores to staff', function (): void {
    $review = PlacementReview::factory()->create();
    intermediateScore($review);
    PlacementAiDraft::factory()->create(['placement_attempt_id' => $review->placement_attempt_id]);
    PlacementIntegrityEvent::factory()->create(['placement_attempt_id' => $review->placement_attempt_id]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->get(route('staff.review.show', $review))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('glc/staff/review-show')
            ->where('score.suggested_level', 'intermediate')
            ->where('ai_drafts.writing.confidence', 'medium')
            ->has('integrity_events', 1)
            ->where('suggested_skill_levels.reading', 'intermediate')
            ->where('review.is_assigned_to_me', false)
        );
});

it('exposes the AI provisional scoring panel props: objective counts, guideline titles, and model attribution', function (): void {
    $review = PlacementReview::factory()->create();

    $items = PlacementItem::factory()->count(2)->create([
        'section' => PlacementSection::Reading,
        'type' => PlacementItemType::Question,
        'correct_option' => 0,
    ]);

    PlacementAnswer::factory()->create([
        'placement_attempt_id' => $review->placement_attempt_id,
        'placement_item_id' => $items[0]->id,
        'response' => ['selected' => 0],
    ]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->get(route('staff.review.show', $review))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('objective_breakdown.reading.correct', 1)
            ->where('objective_breakdown.reading.total', 2)
            ->where('objective_breakdown.reading.percentage', 50)
            ->where('writing_guidelines.customized', false)
            ->where('writing_guidelines.titles.0', 'Grammar accuracy')
            ->where('ai_models.writing.provider', 'Google Gemini')
            ->where('ai_models.speaking.model', 'Gemini 2.5 Flash (audio)')
        );
});

it('exposes the AI recommendation and prefers its levels for the suggested skill levels', function (): void {
    $review = PlacementReview::factory()->create();
    intermediateScore($review);

    PlacementAiRecommendation::factory()->create([
        'placement_attempt_id' => $review->placement_attempt_id,
        'recommended_level' => GlcLevel::UpperIntermediate,
        'skill_levels' => [
            'reading' => 'upper_intermediate',
            'grammar_vocabulary' => 'intermediate',
            'listening' => 'intermediate',
            'writing' => 'intermediate',
            'speaking' => 'pre_intermediate',
        ],
    ]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->get(route('staff.review.show', $review))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('ai_recommendation.status', 'completed')
            ->where('ai_recommendation.recommended_level', 'upper_intermediate')
            ->where('ai_recommendation.confidence', 'medium')
            ->has('ai_recommendation.skill_summaries')
            ->where('suggested_skill_levels.reading', 'upper_intermediate')
            ->where('suggested_skill_levels.speaking', 'pre_intermediate')
        );
});

it('falls back to formula-based suggested skill levels when the recommendation failed', function (): void {
    $review = PlacementReview::factory()->create();
    intermediateScore($review);

    PlacementAiRecommendation::factory()->failed()->create([
        'placement_attempt_id' => $review->placement_attempt_id,
    ]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->get(route('staff.review.show', $review))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('ai_recommendation.status', 'failed')
            ->where('suggested_skill_levels.reading', 'intermediate')
        );
});

it('accepts the AI-recommended levels without requiring an override reason', function (): void {
    $review = PlacementReview::factory()->create(['status' => PlacementReviewStatus::InReview]);
    intermediateScore($review);

    PlacementAiRecommendation::factory()->create([
        'placement_attempt_id' => $review->placement_attempt_id,
        'recommended_level' => GlcLevel::UpperIntermediate,
        'skill_levels' => [
            'reading' => 'upper_intermediate',
            'grammar_vocabulary' => 'intermediate',
            'listening' => 'intermediate',
            'writing' => 'intermediate',
            'speaking' => 'intermediate',
        ],
    ]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->put(route('staff.review.decision', $review), decisionPayload('upper_intermediate', ['reading' => 'upper_intermediate']))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($review->refresh()->final_level)->toBe(GlcLevel::UpperIntermediate)
        ->and($review->override_reason)->toBeNull();
});

it('requires an override reason when deviating from the AI recommendation', function (): void {
    $review = PlacementReview::factory()->create(['status' => PlacementReviewStatus::InReview]);
    intermediateScore($review);

    PlacementAiRecommendation::factory()->create([
        'placement_attempt_id' => $review->placement_attempt_id,
        'recommended_level' => GlcLevel::UpperIntermediate,
    ]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->put(route('staff.review.decision', $review), decisionPayload('intermediate'))
        ->assertSessionHasErrors('override_reason');
});

it('tells the assigned reviewer the review is theirs via is_assigned_to_me', function (): void {
    $teacher = User::factory()->teacher()->create();
    $review = PlacementReview::factory()->create([
        'assigned_to' => $teacher->id,
        'status' => PlacementReviewStatus::InReview,
    ]);

    actingAs($teacher)
        ->get(route('staff.review.show', $review))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('review.is_assigned_to_me', true));
});

it('persists the integrity flag on first load when integrity events exist', function (): void {
    $review = PlacementReview::factory()->create();
    PlacementIntegrityEvent::factory()->dualDevice()->create([
        'placement_attempt_id' => $review->placement_attempt_id,
    ]);

    actingAs(User::factory()->admin()->create())
        ->get(route('staff.review.show', $review))
        ->assertOk();

    expect($review->refresh()->hasFlag('integrity'))->toBeTrue();
});

it('blocks teachers from reviews assigned to someone else', function (): void {
    $review = PlacementReview::factory()->create([
        'assigned_to' => User::factory()->teacher()->create()->id,
    ]);

    actingAs(User::factory()->teacher()->create())
        ->get(route('staff.review.show', $review))
        ->assertForbidden();
});

it('confirms suggested values without requiring an override reason', function (): void {
    $teacher = User::factory()->teacher()->create();
    $review = PlacementReview::factory()->create([
        'assigned_to' => $teacher->id,
        'status' => PlacementReviewStatus::InReview,
    ]);
    intermediateScore($review);

    actingAs($teacher)
        ->put(route('staff.review.decision', $review), decisionPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $review->refresh();

    expect($review->final_level)->toBe(GlcLevel::Intermediate)
        ->and($review->override_reason)->toBeNull()
        ->and(AuditLog::query()->whereIn('action', [AuditAction::LevelOverridden, AuditAction::ScoreOverridden])->count())->toBe(0);
});

it('requires an override reason for any deviation from suggested values', function (): void {
    $review = PlacementReview::factory()->create(['status' => PlacementReviewStatus::InReview]);
    intermediateScore($review);

    actingAs(User::factory()->academicSupervisor()->create())
        ->put(route('staff.review.decision', $review), decisionPayload('upper_intermediate'))
        ->assertSessionHasErrors('override_reason');

    expect($review->refresh()->final_level)->toBeNull();
});

it('records overrides with reason, audit rows, and before/after details', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $review = PlacementReview::factory()->create(['status' => PlacementReviewStatus::InReview]);
    intermediateScore($review);

    actingAs($supervisor)
        ->put(route('staff.review.decision', $review), [
            ...decisionPayload('upper_intermediate', ['listening' => 'pre_intermediate']),
            'override_reason' => 'Listening answers show stronger comprehension than the raw score suggests.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $review->refresh();

    expect($review->final_level)->toBe(GlcLevel::UpperIntermediate)
        ->and($review->override_reason)->not->toBeNull()
        ->and($review->overridden_by)->toBe($supervisor->id)
        ->and($review->skill_levels['listening'])->toBe('pre_intermediate');

    $levelLog = AuditLog::query()->where('action', AuditAction::LevelOverridden)->firstOrFail();
    expect($levelLog->details['before'])->toBe('intermediate')
        ->and($levelLog->details['after'])->toBe('upper_intermediate')
        ->and($levelLog->actor_id)->toBe($supervisor->id);

    $scoreLog = AuditLog::query()->where('action', AuditAction::ScoreOverridden)->firstOrFail();
    expect($scoreLog->details['changes']['listening'])->toBe([
        'before' => 'intermediate',
        'after' => 'pre_intermediate',
    ]);
});

it('approves an in-review decision and audits it', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $review = PlacementReview::factory()->create(['status' => PlacementReviewStatus::InReview]);
    intermediateScore($review);

    actingAs($supervisor)->put(route('staff.review.decision', $review), decisionPayload());

    actingAs($supervisor)
        ->post(route('staff.review.approve', $review))
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas(
            'success',
            'Final approval given. Approve the parent summary before sending the result.',
        );

    $review->refresh();

    expect($review->status)->toBe(PlacementReviewStatus::Approved)
        ->and($review->approved_at)->not->toBeNull()
        ->and($review->approved_by)->toBe($supervisor->id)
        ->and(AuditLog::query()->where('action', AuditAction::ReviewApproved)->exists())->toBeTrue();
});

it('tells staff they can send immediately when the parent summary was already approved', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $review = PlacementReview::factory()->create([
        'status' => PlacementReviewStatus::InReview,
        'narrative' => [
            'strengths' => 'Strong reading.',
            'areas_to_improve' => 'Listening accuracy.',
            'recommendation' => 'Intermediate placement.',
            'next_steps' => 'Enrol in Intermediate.',
        ],
        'narrative_approved_at' => now(),
        'narrative_approved_by' => $supervisor->id,
    ]);
    intermediateScore($review);

    actingAs($supervisor)->put(route('staff.review.decision', $review), decisionPayload());

    actingAs($supervisor)
        ->post(route('staff.review.approve', $review))
        ->assertRedirect()
        ->assertSessionHas(
            'success',
            'Final approval given. You can now preview the PDF and send the result.',
        );
});

it('requires starting the review before approval, with a plain-language message', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();

    $pending = PlacementReview::factory()->create();
    actingAs($supervisor)
        ->post(route('staff.review.approve', $pending))
        ->assertSessionHasErrors(['status' => 'Start the review before approving the result.']);

    $undecided = PlacementReview::factory()->create(['status' => PlacementReviewStatus::InReview]);
    actingAs($supervisor)
        ->post(route('staff.review.approve', $undecided))
        ->assertSessionHasErrors(['status' => 'Choose the final level and a level for every section before approving.']);
});

it('stores internal notes visible to staff', function (): void {
    $teacher = User::factory()->teacher()->create();
    $review = PlacementReview::factory()->create(['assigned_to' => $teacher->id]);

    actingAs($teacher)
        ->post(route('staff.review.notes.store', $review), [
            'note' => 'Candidate seemed nervous during speaking; level is fair.',
        ])
        ->assertRedirect();

    expect($review->notes()->count())->toBe(1)
        ->and($review->notes()->first()->author_id)->toBe($teacher->id);

    actingAs($teacher)
        ->get(route('staff.review.show', $review))
        ->assertInertia(fn (Assert $page) => $page
            ->where('notes.0.note', 'Candidate seemed nervous during speaking; level is fair.'));
});

it('rejects decision changes after the result was sent', function (): void {
    $review = PlacementReview::factory()->approved()->create(['status' => PlacementReviewStatus::Sent]);

    actingAs(User::factory()->admin()->create())
        ->put(route('staff.review.decision', $review), decisionPayload())
        ->assertStatus(422);
});
