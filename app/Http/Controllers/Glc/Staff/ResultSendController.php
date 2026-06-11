<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\PlacementReviewStatus;
use App\Mail\Glc\PlacementResultMail;
use App\Models\Glc\PlacementResultLink;
use App\Models\Glc\PlacementReview;
use App\Models\User;
use App\Services\Glc\AuditLogger;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final readonly class ResultSendController
{
    public function __construct(private AuditLogger $audit) {}

    public function store(Request $request, PlacementReview $review, #[CurrentUser] User $user): RedirectResponse
    {
        if (! in_array($review->status, [PlacementReviewStatus::Approved, PlacementReviewStatus::Sent], true)) {
            throw ValidationException::withMessages([
                'status' => 'The review must be approved before the result can be sent.',
            ]);
        }

        if (! $review->canGeneratePdf()) {
            throw ValidationException::withMessages([
                'status' => 'The narrative must be approved before the result can be sent.',
            ]);
        }

        $attempt = $review->attempt;

        if ($attempt->isMinor()) {
            $request->validate(
                ['guardian_consent' => ['accepted']],
                ['guardian_consent.accepted' => 'Guardian consent must be confirmed before sending a result to a minor candidate.'],
            );

            if (! $review->hasFlag('guardian_consent_confirmed')) {
                $review->update(['flags' => [...($review->flags ?? []), 'guardian_consent_confirmed']]);
            }

            $this->audit->log(AuditAction::ConsentConfirmed, $user, $review, [
                'context' => 'placement_result_send',
                'candidate_email' => $attempt->candidate_email,
            ]);
        }

        $link = PlacementResultLink::query()->create([
            'placement_attempt_id' => $attempt->id,
            'token' => PlacementResultLink::generateToken(),
            'email_to' => $attempt->candidate_email,
            'expires_at' => now()->addDays(config()->integer('glc.placement.result_link_days', 30)),
            'sent_at' => now(),
            'sent_by' => $user->id,
        ]);

        Mail::to($attempt->candidate_email)->send(new PlacementResultMail($link));

        $review->update(['status' => PlacementReviewStatus::Sent]);

        $this->audit->log(AuditAction::ResultSent, $user, $review, [
            'result_link_id' => $link->id,
            'email_to' => $link->email_to,
            'expires_at' => $link->expires_at->toDateTimeString(),
        ]);

        return back()->with('success', 'Result sent to '.$attempt->candidate_email.'.');
    }
}
