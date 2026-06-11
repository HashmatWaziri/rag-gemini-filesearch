<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\PlacementReviewStatus;
use App\Mail\Glc\PlacementResultMail;
use App\Models\Glc\AuditLog;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementResultLink;
use App\Models\Glc\PlacementReview;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Mail::fake();
});

it('blocks sending before the review is approved', function (): void {
    $review = PlacementReview::factory()->create(['status' => PlacementReviewStatus::InReview]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->post(route('staff.review.send', $review))
        ->assertSessionHasErrors('status');

    Mail::assertNothingSent();
    expect(PlacementResultLink::query()->count())->toBe(0);
});

it('blocks sending when the narrative is not approved', function (): void {
    $review = PlacementReview::factory()->approved()->create(['narrative_approved_at' => null]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->post(route('staff.review.send', $review))
        ->assertSessionHasErrors('status');

    Mail::assertNothingSent();
});

it('sends one transactional mail with a 30-day secure link and audits it', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $attempt = PlacementAttempt::factory()->submitted()->create([
        'candidate_email' => 'candidate@example.com',
        'candidate_age' => 25,
    ]);
    $review = PlacementReview::factory()->approved()->create(['placement_attempt_id' => $attempt->id]);

    actingAs($supervisor)
        ->post(route('staff.review.send', $review))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $link = PlacementResultLink::query()->firstOrFail();

    expect($link->email_to)->toBe('candidate@example.com')
        ->and($link->expires_at->toDateTimeString())->toBe(now()->addDays(30)->toDateTimeString())
        ->and($link->sent_by)->toBe($supervisor->id)
        ->and(mb_strlen($link->token))->toBe(64)
        ->and($review->refresh()->status)->toBe(PlacementReviewStatus::Sent);

    Mail::assertSent(PlacementResultMail::class, function (PlacementResultMail $mail) use ($link): bool {
        return $mail->hasTo('candidate@example.com') && $mail->link->is($link);
    });
    Mail::assertSentCount(1);

    $log = AuditLog::query()->where('action', AuditAction::ResultSent)->firstOrFail();
    expect($log->actor_id)->toBe($supervisor->id)
        ->and($log->details['email_to'])->toBe('candidate@example.com');
});

it('contains the secure result URL in the mail body', function (): void {
    $link = PlacementResultLink::factory()->create();

    $rendered = new PlacementResultMail($link)->render();

    expect($rendered)->toContain(route('placement.result.show', $link->token));
});

it('blocks sending to minors without the guardian consent checkbox', function (): void {
    $attempt = PlacementAttempt::factory()->minor()->submitted()->create();
    $review = PlacementReview::factory()->approved()->create(['placement_attempt_id' => $attempt->id]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->post(route('staff.review.send', $review))
        ->assertSessionHasErrors('guardian_consent');

    Mail::assertNothingSent();

    expect(PlacementResultLink::query()->count())->toBe(0)
        ->and($review->refresh()->status)->toBe(PlacementReviewStatus::Approved);
});

it('sends to minors with consent confirmed, persisting the flag and audit', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $attempt = PlacementAttempt::factory()->minor()->submitted()->create();
    $review = PlacementReview::factory()->approved()->create(['placement_attempt_id' => $attempt->id]);

    actingAs($supervisor)
        ->post(route('staff.review.send', $review), ['guardian_consent' => true])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($review->refresh()->hasFlag('guardian_consent_confirmed'))->toBeTrue()
        ->and($review->status)->toBe(PlacementReviewStatus::Sent)
        ->and(AuditLog::query()->where('action', AuditAction::ConsentConfirmed)->exists())->toBeTrue();

    Mail::assertSentCount(1);
});

it('does not require guardian consent for adult candidates', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create(['candidate_age' => 30]);
    $review = PlacementReview::factory()->approved()->create(['placement_attempt_id' => $attempt->id]);

    actingAs(User::factory()->teacher()->create())
        ->post(route('staff.review.send', $review))
        ->assertSessionHasNoErrors();

    Mail::assertSentCount(1);
});

it('allows resending with a fresh link', function (): void {
    $attempt = PlacementAttempt::factory()->submitted()->create(['candidate_age' => 30]);
    $review = PlacementReview::factory()->approved()->create(['placement_attempt_id' => $attempt->id]);
    $supervisor = User::factory()->academicSupervisor()->create();

    actingAs($supervisor)->post(route('staff.review.send', $review))->assertSessionHasNoErrors();
    actingAs($supervisor)->post(route('staff.review.send', $review->refresh()))->assertSessionHasNoErrors();

    expect(PlacementResultLink::query()->count())->toBe(2)
        ->and(PlacementResultLink::query()->pluck('token')->unique())->toHaveCount(2);

    Mail::assertSentCount(2);
});
