<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementAiDraftStatus;
use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementScore;

final class ScoringService
{
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

        $composite = $available === []
            ? null
            : round(array_sum($available) / count($available), 2);

        $varianceFlagged = count($available) >= 2
            && (max($available) - min($available)) >= (float) config('glc.placement.variance_flag_threshold', 30.0);

        $score = PlacementScore::query()->updateOrCreate(
            ['placement_attempt_id' => $attempt->id],
            [
                'section_scores' => $sectionScores,
                'composite' => $composite,
                'suggested_level' => $composite === null ? null : GlcLevel::fromComposite($composite),
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

            if (! $item instanceof PlacementItem || ! $item->isScoreable()) {
                continue;
            }

            $selected = $answer->response['selected'] ?? null;
            $isCorrect = $selected !== null && (int) $selected === $item->correct_option;

            $answer->update(['is_correct' => $isCorrect]);
        }
    }

    private function objectiveSectionPercentage(PlacementAttempt $attempt, PlacementSection $section): ?float
    {
        $itemIds = PlacementItem::query()
            ->active()
            ->forSection($section)
            ->where('type', PlacementItemType::Question)
            ->whereNotNull('correct_option')
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
