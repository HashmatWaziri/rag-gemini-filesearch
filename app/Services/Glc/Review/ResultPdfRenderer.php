<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementReview;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DompdfWrapper;

final class ResultPdfRenderer
{
    /**
     * @return array<string, mixed>
     */
    public function viewData(PlacementReview $review): array
    {
        $attempt = $review->attempt;

        $skillLevels = [];

        foreach (PlacementSection::ordered() as $section) {
            $value = $review->skill_levels[$section->value] ?? null;

            $skillLevels[] = [
                'skill' => $section->label(),
                'level' => is_string($value) ? GlcLevel::from($value)->label() : '—',
            ];
        }

        $narrative = $review->narrative ?? [];

        return [
            'candidateName' => $attempt->candidate_name,
            'testDate' => $attempt->submitted_at?->format('j F Y') ?? '—',
            'skillLevels' => $skillLevels,
            'overallLevel' => $review->final_level?->label() ?? '—',
            'narrative' => [
                'Strengths' => $narrative['strengths'] ?? null,
                'Areas to Improve' => $narrative['areas_to_improve'] ?? null,
                'Recommendation' => $narrative['recommendation'] ?? null,
                'Next Steps' => $narrative['next_steps'] ?? null,
            ],
        ];
    }

    public function pdf(PlacementReview $review): DompdfWrapper
    {
        return Pdf::loadView('glc.placement-result-pdf', $this->viewData($review));
    }
}
