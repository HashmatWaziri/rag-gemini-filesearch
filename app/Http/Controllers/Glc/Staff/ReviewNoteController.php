<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use App\Enums\Glc\UserRole;
use App\Models\Glc\PlacementReview;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class ReviewNoteController
{
    public function store(Request $request, PlacementReview $review, #[CurrentUser] User $user): RedirectResponse
    {
        if ($user->role === UserRole::Teacher) {
            abort_unless($review->assigned_to === null || $review->assigned_to === $user->id, 403);
        }

        $data = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $review->notes()->create([
            'author_id' => $user->id,
            'note' => $data['note'],
        ]);

        return back()->with('success', 'Note added.');
    }
}
