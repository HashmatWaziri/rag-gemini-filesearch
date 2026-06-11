<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementReviewStatus;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementIntegrityEvent;
use App\Models\Glc\PlacementReview;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
});

it('redirects guests and blocks students from the review queue', function (): void {
    $this->get(route('staff.review.index'))->assertRedirect(route('login'));

    actingAs(User::factory()->student()->create())
        ->get(route('staff.review.index'))
        ->assertForbidden();
});

it('shows teachers only their own or unassigned reviews', function (): void {
    $teacher = User::factory()->teacher()->create();
    $otherTeacher = User::factory()->teacher()->create();

    $mine = PlacementReview::factory()->create(['assigned_to' => $teacher->id]);
    $unassigned = PlacementReview::factory()->create();
    PlacementReview::factory()->create(['assigned_to' => $otherTeacher->id]);

    actingAs($teacher)
        ->get(route('staff.review.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('glc/staff/review-index')
            ->has('reviews.data', 2)
            ->where('supervises', false)
        );
});

it('shows supervisors the full queue including flagged and assigned reviews', function (): void {
    $teacher = User::factory()->teacher()->create();

    PlacementReview::factory()->create(['assigned_to' => $teacher->id]);
    PlacementReview::factory()->flagged()->create();
    PlacementReview::factory()->create();

    actingAs(User::factory()->academicSupervisor()->create())
        ->get(route('staff.review.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('reviews.data', 3)
            ->where('supervises', true)
        );
});

it('filters by status, flag, assignee, date range, and candidate search', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();

    $pending = PlacementReview::factory()->create([
        'placement_attempt_id' => PlacementAttempt::factory()->submitted()->create([
            'candidate_name' => 'Aisha Rahman',
            'submitted_at' => now()->subDays(2),
        ])->id,
    ]);
    $approved = PlacementReview::factory()->approved()->create([
        'placement_attempt_id' => PlacementAttempt::factory()->submitted()->create([
            'candidate_name' => 'Bilal Hassan',
            'submitted_at' => now(),
        ])->id,
    ]);
    $flagged = PlacementReview::factory()->flagged()->create([
        'placement_attempt_id' => PlacementAttempt::factory()->submitted()->create([
            'candidate_name' => 'Chen Wei',
            'submitted_at' => now()->subDays(10),
        ])->id,
    ]);

    actingAs($supervisor)
        ->get(route('staff.review.index', ['status' => 'approved']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('reviews.data', 1)
            ->where('reviews.data.0.candidate_name', 'Bilal Hassan'));

    actingAs($supervisor)
        ->get(route('staff.review.index', ['flag' => 'variance']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('reviews.data', 1)
            ->where('reviews.data.0.candidate_name', 'Chen Wei'));

    actingAs($supervisor)
        ->get(route('staff.review.index', ['search' => 'aisha']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('reviews.data', 1)
            ->where('reviews.data.0.candidate_name', 'Aisha Rahman'));

    actingAs($supervisor)
        ->get(route('staff.review.index', ['from' => now()->subDays(5)->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page->has('reviews.data', 2));

    actingAs($supervisor)
        ->get(route('staff.review.index', ['assignee' => 'unassigned']))
        ->assertInertia(fn (Assert $page) => $page->has('reviews.data', 3));
});

it('surfaces integrity events as a flag source in the queue payload', function (): void {
    $review = PlacementReview::factory()->create();
    PlacementIntegrityEvent::factory()->dualDevice()->create([
        'placement_attempt_id' => $review->placement_attempt_id,
    ]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->get(route('staff.review.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('reviews.data.0.has_integrity_events', true));
});

it('lets a teacher claim an unassigned review which moves it to in_review', function (): void {
    $teacher = User::factory()->teacher()->create();
    $review = PlacementReview::factory()->create();

    actingAs($teacher)
        ->post(route('staff.review.claim', $review))
        ->assertRedirect();

    expect($review->refresh()->assigned_to)->toBe($teacher->id)
        ->and($review->status)->toBe(PlacementReviewStatus::InReview);
});

it('prevents a teacher from claiming a review assigned to someone else', function (): void {
    $otherTeacher = User::factory()->teacher()->create();
    $review = PlacementReview::factory()->create(['assigned_to' => $otherTeacher->id]);

    actingAs(User::factory()->teacher()->create())
        ->post(route('staff.review.claim', $review))
        ->assertForbidden();
});

it('lets supervisors assign a review to any staff member', function (): void {
    $teacher = User::factory()->teacher()->create();
    $review = PlacementReview::factory()->create();

    actingAs(User::factory()->academicSupervisor()->create())
        ->post(route('staff.review.assign', $review), ['user_id' => $teacher->id])
        ->assertRedirect();

    expect($review->refresh()->assigned_to)->toBe($teacher->id)
        ->and($review->status)->toBe(PlacementReviewStatus::InReview);
});

it('prevents teachers from assigning reviews to others', function (): void {
    $review = PlacementReview::factory()->create();

    actingAs(User::factory()->teacher()->create())
        ->post(route('staff.review.assign', $review), ['user_id' => User::factory()->teacher()->create()->id])
        ->assertForbidden();
});

it('rejects assigning reviews to non-staff users', function (): void {
    $review = PlacementReview::factory()->create();
    $student = User::factory()->student()->create();

    actingAs(User::factory()->admin()->create())
        ->post(route('staff.review.assign', $review), ['user_id' => $student->id])
        ->assertSessionHasErrors('user_id');
});
