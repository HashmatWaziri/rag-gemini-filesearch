<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementReviewStatus;
use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\UserRole;
use App\Models\Glc\PlacementReview;
use App\Models\User;
use App\Services\Glc\AuditLogger;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class ReviewDecisionController
{
    public function __construct(private AuditLogger $audit) {}

    public function update(Request $request, PlacementReview $review, #[CurrentUser] User $user): RedirectResponse
    {
        $this->authorizeAccess($review, $user);
        abort_if($review->status === PlacementReviewStatus::Sent, 422, 'This result has already been sent, so the levels can no longer change.');

        $rules = [
            'final_level' => ['required', Rule::enum(GlcLevel::class)],
            'skill_levels' => ['required', 'array'],
            'override_reason' => ['nullable', 'string', 'max:2000'],
        ];

        foreach (PlacementSection::ordered() as $section) {
            $rules['skill_levels.'.$section->value] = ['required', Rule::enum(GlcLevel::class)];
        }

        $data = $request->validate($rules);

        $score = $review->attempt->score;
        $suggestedFinal = $score?->suggested_level;

        $finalLevel = GlcLevel::from($data['final_level']);
        $levelDeviation = $suggestedFinal !== null && $finalLevel !== $suggestedFinal;

        $skillDeviations = [];

        foreach (PlacementSection::ordered() as $section) {
            $pct = $score?->section_scores[$section->value] ?? null;
            $suggested = is_numeric($pct) ? GlcLevel::fromComposite((float) $pct) : null;
            $chosen = GlcLevel::from($data['skill_levels'][$section->value]);

            if ($suggested !== null && $chosen !== $suggested) {
                $skillDeviations[$section->value] = [
                    'before' => $suggested->value,
                    'after' => $chosen->value,
                ];
            }
        }

        $hasDeviation = $levelDeviation || $skillDeviations !== [];
        $reason = isset($data['override_reason']) && is_string($data['override_reason']) ? mb_trim($data['override_reason']) : '';

        if ($hasDeviation && $reason === '') {
            throw ValidationException::withMessages([
                'override_reason' => 'Please add a short reason — the levels you chose differ from the automatic suggestion.',
            ]);
        }

        $review->update([
            'final_level' => $finalLevel,
            'skill_levels' => $data['skill_levels'],
            'override_reason' => $hasDeviation ? $reason : null,
            'overridden_by' => $hasDeviation ? $user->id : null,
        ]);

        if ($levelDeviation) {
            $this->audit->log(AuditAction::LevelOverridden, $user, $review, [
                'before' => $suggestedFinal?->value,
                'after' => $finalLevel->value,
                'reason' => $reason,
            ]);
        }

        if ($skillDeviations !== []) {
            $this->audit->log(AuditAction::ScoreOverridden, $user, $review, [
                'changes' => $skillDeviations,
                'reason' => $reason,
            ]);
        }

        return back()->with('success', 'Levels saved.');
    }

    public function approve(PlacementReview $review, #[CurrentUser] User $user): RedirectResponse
    {
        $this->authorizeAccess($review, $user);

        if ($review->status !== PlacementReviewStatus::InReview) {
            throw ValidationException::withMessages([
                'status' => 'Start the review before approving the result.',
            ]);
        }

        if ($review->final_level === null || $review->skill_levels === null) {
            throw ValidationException::withMessages([
                'status' => 'Choose the final level and a level for every section before approving.',
            ]);
        }

        $review->update([
            'status' => PlacementReviewStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);

        $this->audit->log(AuditAction::ReviewApproved, $user, $review, [
            'final_level' => $review->final_level->value,
        ]);

        return back()->with('success', 'Final approval given. You can now send the result once the parent summary is approved.');
    }

    private function authorizeAccess(PlacementReview $review, User $user): void
    {
        if ($user->role === UserRole::Teacher) {
            abort_unless($review->assigned_to === null || $review->assigned_to === $user->id, 403);
        }
    }
}
