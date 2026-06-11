<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementResultLink;
use App\Models\Glc\PlacementReview;
use App\Services\Glc\Review\ResultPdfRenderer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final readonly class PublicResultController
{
    public function __construct(private ResultPdfRenderer $renderer) {}

    public function show(Request $request, string $token): Response
    {
        $link = $this->resolveLink($token);
        $review = $link?->attempt->review;

        if ($link === null || ! $review instanceof PlacementReview || ! $review->canGeneratePdf()) {
            return Inertia::render('glc/staff/result-expired')
                ->toResponse($request)
                ->setStatusCode(404);
        }

        $link->update(['last_viewed_at' => now()]);

        $attempt = $link->attempt;

        $skillLevels = [];

        foreach (PlacementSection::ordered() as $section) {
            $value = $review->skill_levels[$section->value] ?? null;

            $skillLevels[] = [
                'skill' => $section->label(),
                'level' => is_string($value) ? GlcLevel::from($value)->label() : null,
            ];
        }

        return Inertia::render('glc/staff/result-public', [
            'candidateName' => $attempt->candidate_name,
            'testDate' => $attempt->submitted_at?->format('j F Y'),
            'overallLevel' => $review->final_level?->label(),
            'skillLevels' => $skillLevels,
            'narrative' => [
                'strengths' => $review->narrative['strengths'] ?? null,
                'areas_to_improve' => $review->narrative['areas_to_improve'] ?? null,
                'recommendation' => $review->narrative['recommendation'] ?? null,
                'next_steps' => $review->narrative['next_steps'] ?? null,
            ],
            'downloadUrl' => route('placement.result.download', $token),
            'expiresAt' => $link->expires_at->format('j F Y'),
        ])->toResponse($request);
    }

    public function download(string $token): Response
    {
        $link = $this->resolveLink($token);
        $review = $link?->attempt->review;

        abort_unless($link !== null && $review instanceof PlacementReview && $review->canGeneratePdf(), 404);

        $link->update(['last_viewed_at' => now()]);

        return $this->renderer->download($review);
    }

    private function resolveLink(string $token): ?PlacementResultLink
    {
        $link = PlacementResultLink::query()
            ->with('attempt.review')
            ->where('token', $token)
            ->first();

        if ($link === null || $link->isExpired()) {
            return null;
        }

        return $link;
    }
}
