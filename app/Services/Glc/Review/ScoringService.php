<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use App\Enums\Glc\PlacementAiDraftStatus;
use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementScore;
use App\Services\Glc\Admin\PlacementScoringSettings;

final class ScoringService
{
    public function __construct(
        private readonly ObjectiveAnswerGrader $grader,
        private readonly PlacementScoringSettings $scoringSettings,
    ) {}

    public function scoreAttempt(PlacementAttempt $attempt): PlacementScore
    {
        $this->gradeObjectiveAnswers($attempt);

        $sectionScores = [];

        foreach (PlacementSection::ordered() as $section) {
            $sectionScores[$section->value] = $section->isObjective()
                ? $this->objectiveSectionPercentage($attempt, $section)
                : $this->draftSectionPercentage($attempt, $section);
        }

        $available = array_values(array_filter($sectionScores, fn (?float $value): bool => $value !== null));

        $composite = $this->scoringSettings->compositeFromSectionScores($sectionScores);

        $varianceFlagged = count($available) >= 2
            && (max($available) - min($available)) >= $this->scoringSettings->varianceFlagThreshold();

        $score = PlacementScore::query()->updateOrCreate(
            ['placement_attempt_id' => $attempt->id],
            [
                'section_scores' => $sectionScores,
                'composite' => $composite,
                'suggested_level' => $composite === null ? null : $this->scoringSettings->levelFromComposite($composite),
                'variance_flagged' => $varianceFlagged,
                'computed_at' => now(),
            ],
        );

        if ($varianceFlagged) {
            $this->flagReviewVariance($attempt);
        }

        return $score;
    }

    private function gradeObjectiveAnswers(PlacementAttempt $attempt): void
    {
        $answers = $attempt->answers()->with('item')->get();

        foreach ($answers as $answer) {
            $item = $answer->item;

            if (! $item instanceof PlacementItem || ! $this->grader->isGradable($item)) {
                continue;
            }

            $answer->update(['is_correct' => $this->grader->isCorrect($item, $answer->response)]);
        }
    }

    private function objectiveSectionPercentage(PlacementAttempt $attempt, PlacementSection $section): ?float
    {
        $itemIds = PlacementItem::query()
            ->active()
            ->forSection($section)
            ->where('type', PlacementItemType::Question)
            ->get()
            ->filter(fn (PlacementItem $item): bool => $this->grader->isGradable($item))
            ->pluck('id');

        if ($itemIds->isEmpty()) {
            return null;
        }

        $correct = $attempt->answers()
            ->whereIn('placement_item_id', $itemIds)
            ->where('is_correct', true)
            ->count();

        return round(($correct / $itemIds->count()) * 100, 2);
    }

    private function draftSectionPercentage(PlacementAttempt $attempt, PlacementSection $section): ?float
    {
        $draft = $attempt->aiDrafts()
            ->where('section', $section)
            ->where('status', PlacementAiDraftStatus::Completed)
            ->first();

        return $draft?->asPercentage();
    }

    private function flagReviewVariance(PlacementAttempt $attempt): void
    {
        $review = $attempt->review;

        if ($review === null || $review->hasFlag('variance')) {
            return;
        }

        $review->update(['flags' => [...($review->flags ?? []), 'variance']]);
    }
}
