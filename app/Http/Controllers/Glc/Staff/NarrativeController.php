<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\PlacementReviewStatus;
use App\Enums\Glc\UserRole;
use App\Models\Glc\PlacementReview;
use App\Models\User;
use App\Services\Glc\AuditLogger;
use App\Services\Glc\Review\NarrativeDraftService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class NarrativeController
{
    public function __construct(private AuditLogger $audit) {}

    public function update(Request $request, PlacementReview $review, #[CurrentUser] User $user): RedirectResponse
    {
        $this->authorizeAccess($review, $user);
        abort_if($review->status === PlacementReviewStatus::Sent, 422, 'This result has already been sent, so the parent summary can no longer change.');

        $data = $request->validate([
            'strengths' => ['nullable', 'string', 'max:5000'],
            'areas_to_improve' => ['nullable', 'string', 'max:5000'],
            'recommendation' => ['nullable', 'string', 'max:5000'],
            'next_steps' => ['nullable', 'string', 'max:5000'],
        ]);

        $review->update([
            'narrative' => [
                'strengths' => $data['strengths'] ?? null,
                'areas_to_improve' => $data['areas_to_improve'] ?? null,
                'recommendation' => $data['recommendation'] ?? null,
                'next_steps' => $data['next_steps'] ?? null,
            ],
            'narrative_approved_at' => null,
            'narrative_approved_by' => null,
        ]);

        return back()->with('success', 'Parent summary saved. Approve it when it is ready to appear on the result.');
    }

    public function draft(PlacementReview $review, #[CurrentUser] User $user, NarrativeDraftService $service): JsonResponse
    {
        $this->authorizeAccess($review, $user);

        try {
            return response()->json(['narrative' => $service->draft($review)]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The AI suggestion is unavailable right now. You can write the parent summary yourself.',
            ], 422);
        }
    }

    public function approve(PlacementReview $review, #[CurrentUser] User $user): RedirectResponse
    {
        $this->authorizeAccess($review, $user);
        abort_if($review->status === PlacementReviewStatus::Sent, 422, 'This result has already been sent, so the parent summary can no longer change.');

        $narrative = $review->narrative ?? [];

        foreach (['strengths', 'areas_to_improve', 'recommendation', 'next_steps'] as $field) {
            $value = $narrative[$field] ?? null;

            if (! is_string($value) || mb_trim($value) === '') {
                throw ValidationException::withMessages([
                    'narrative' => 'Fill in all four parent summary fields before approving.',
                ]);
            }
        }

        $review->update([
            'narrative_approved_at' => now(),
            'narrative_approved_by' => $user->id,
        ]);

        $this->audit->log(AuditAction::NarrativeApproved, $user, $review);

        return back()->with('success', 'Parent summary approved.');
    }

    private function authorizeAccess(PlacementReview $review, User $user): void
    {
        if ($user->role === UserRole::Teacher) {
            abort_unless($review->assigned_to === null || $review->assigned_to === $user->id, 403);
        }
    }
}
