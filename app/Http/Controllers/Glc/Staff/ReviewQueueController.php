<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use App\Enums\Glc\PlacementReviewStatus;
use App\Enums\Glc\UserRole;
use App\Models\Glc\PlacementReview;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ReviewQueueController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(PlacementReviewStatus::class)],
            'assignee' => ['nullable', 'string', 'max:32'],
            'flag' => ['nullable', 'string', 'max:64'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $supervises = $user->role !== UserRole::Teacher;

        $query = PlacementReview::query()
            ->with(['attempt.score', 'assignee'])
            ->when(! $supervises, function (Builder $query) use ($user): void {
                $query->where(function (Builder $query) use ($user): void {
                    $query->where('assigned_to', $user->id)->orWhereNull('assigned_to');
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['flag'] ?? null, fn (Builder $query, string $flag) => $query->whereJsonContains('flags', $flag))
            ->when($filters['assignee'] ?? null, function (Builder $query, string $assignee) use ($user): void {
                match ($assignee) {
                    'me' => $query->where('assigned_to', $user->id),
                    'unassigned' => $query->whereNull('assigned_to'),
                    default => $query->where('assigned_to', (int) $assignee),
                };
            })
            ->when($filters['from'] ?? null, function (Builder $query, string $from): void {
                $query->whereHas('attempt', fn (Builder $query) => $query->where('submitted_at', '>=', $from));
            })
            ->when($filters['to'] ?? null, function (Builder $query, string $to): void {
                $query->whereHas('attempt', fn (Builder $query) => $query->whereDate('submitted_at', '<=', $to));
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('attempt', function (Builder $query) use ($search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('candidate_name', 'like', "%{$search}%")
                            ->orWhere('candidate_email', 'like', "%{$search}%");
                    });
                });
            })
            ->orderByRaw("case status when 'pending' then 0 when 'in_review' then 1 when 'approved' then 2 else 3 end")
            ->oldest('created_at');

        $reviews = $query->paginate(25)->withQueryString();

        $staff = $supervises
            ? User::query()->whereIn('role', UserRole::staffValues())->orderBy('name')->get(['id', 'name'])
            : collect();

        return Inertia::render('glc/staff/review-index', [
            'reviews' => [
                'data' => collect($reviews->items())->map(fn (PlacementReview $review): array => [
                    'id' => $review->id,
                    'candidate_name' => $review->attempt->candidate_name,
                    'candidate_email' => $review->attempt->candidate_email,
                    'candidate_age' => $review->attempt->candidate_age,
                    'is_minor' => $review->attempt->isMinor(),
                    'submitted_at' => $review->attempt->submitted_at?->toDateTimeString(),
                    'status' => $review->status->value,
                    'status_label' => $review->status->label(),
                    'flags' => $review->flags ?? [],
                    'has_integrity_events' => $review->attempt->integrityEvents()->exists(),
                    'suggested_level' => $review->attempt->score?->suggested_level?->label(),
                    'variance_flagged' => $review->attempt->score?->variance_flagged ?? false,
                    'assignee' => $review->assignee?->name,
                    'assigned_to' => $review->assigned_to,
                    'can_claim' => $review->assigned_to === null && $review->status === PlacementReviewStatus::Pending,
                    'has_decision' => $review->final_level !== null,
                    'narrative_approved' => $review->narrative_approved_at !== null,
                ])->all(),
                'links' => $reviews->linkCollection(),
                'total' => $reviews->total(),
            ],
            'filters' => $filters,
            'staff' => $staff->map(fn (User $member): array => ['id' => $member->id, 'name' => $member->name]),
            'supervises' => $supervises,
        ]);
    }
}
