<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use App\Enums\Glc\PlacementReviewStatus;
use App\Enums\Glc\UserRole;
use App\Models\Glc\PlacementReview;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final readonly class ReviewAssignmentController
{
    public function claim(PlacementReview $review, #[CurrentUser] User $user): RedirectResponse
    {
        abort_if($review->status === PlacementReviewStatus::Sent, 422, 'This result has already been sent, so the review can no longer change.');

        if ($user->role === UserRole::Teacher) {
            abort_unless($review->assigned_to === null || $review->assigned_to === $user->id, 403);
        }

        $review->update([
            'assigned_to' => $user->id,
            'status' => $review->status === PlacementReviewStatus::Pending ? PlacementReviewStatus::InReview : $review->status,
        ]);

        return back()->with('success', 'This placement test is now assigned to you.');
    }

    public function assign(Request $request, PlacementReview $review, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($user->role === UserRole::Admin || $user->role === UserRole::AcademicSupervisor, 403);
        abort_if($review->status === PlacementReviewStatus::Sent, 422, 'This result has already been sent, so the review can no longer change.');

        $data = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereIn('role', UserRole::staffValues()),
            ],
        ]);

        $review->update([
            'assigned_to' => $data['user_id'],
            'status' => $review->status === PlacementReviewStatus::Pending ? PlacementReviewStatus::InReview : $review->status,
        ]);

        return back()->with('success', 'Reviewer updated.');
    }
}
