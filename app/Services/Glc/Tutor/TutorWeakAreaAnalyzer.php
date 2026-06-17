<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Enums\Glc\TutorViolationCategory;
use App\Enums\Glc\WritingDimension;
use App\Models\Glc\TutorViolation;
use App\Models\Glc\WritingSubmission;
use App\Models\User;
use Illuminate\Support\Collection;

final class TutorWeakAreaAnalyzer
{
    /**
     * @return array{
     *     writing_dimensions: list<array{dimension: string, label: string, average_score: float, sample_count: int}>,
     *     violation_categories: list<array{category: string, label: string, count: int}>,
     *     has_enough_writing: bool
     * }
     */
    public function forStudent(User $student, int $windowDays = 30): array
    {
        $since = now()->subDays($windowDays);

        $writingDimensions = $this->writingDimensionAverages(
            WritingSubmission::query()
                ->where('user_id', $student->id)
                ->where('status', 'completed')
                ->where('created_at', '>=', $since)
                ->get(),
        );

        $violations = TutorViolation::query()
            ->where('user_id', $student->id)
            ->where('occurred_at', '>=', $since)
            ->get();

        return [
            'writing_dimensions' => $writingDimensions['dimensions'],
            'violation_categories' => $this->violationCategoryCounts($violations),
            'has_enough_writing' => $writingDimensions['sample_count'] >= 1,
        ];
    }

    /**
     * @param  list<int>  $studentIds
     * @return array{
     *     writing_dimensions: list<array{dimension: string, label: string, average_score: float, sample_count: int}>,
     *     violation_categories: list<array{category: string, label: string, count: int}>
     * }
     */
    public function cohortSummary(array $studentIds, int $windowDays = 30): array
    {
        if ($studentIds === []) {
            return [
                'writing_dimensions' => [],
                'violation_categories' => [],
            ];
        }

        $since = now()->subDays($windowDays);

        $writingDimensions = $this->writingDimensionAverages(
            WritingSubmission::query()
                ->whereIn('user_id', $studentIds)
                ->where('status', 'completed')
                ->where('created_at', '>=', $since)
                ->get(),
        );

        $violations = TutorViolation::query()
            ->whereIn('user_id', $studentIds)
            ->where('occurred_at', '>=', $since)
            ->get();

        return [
            'writing_dimensions' => $writingDimensions['dimensions'],
            'violation_categories' => $this->violationCategoryCounts($violations),
        ];
    }

    /**
     * @param  Collection<int, WritingSubmission>  $submissions
     * @return array{dimensions: list<array{dimension: string, label: string, average_score: float, sample_count: int}>, sample_count: int}
     */
    private function writingDimensionAverages(Collection $submissions): array
    {
        $totals = [];
        $counts = [];

        foreach (WritingDimension::cases() as $dimension) {
            $totals[$dimension->value] = 0.0;
            $counts[$dimension->value] = 0;
        }

        foreach ($submissions as $submission) {
            $feedback = $submission->feedback;

            if (! is_array($feedback)) {
                continue;
            }

            $dimensions = data_get($feedback, 'dimensions');

            if (! is_array($dimensions)) {
                continue;
            }

            foreach (WritingDimension::cases() as $dimension) {
                $score = data_get($dimensions, $dimension->value.'.score');

                if (! is_numeric($score)) {
                    continue;
                }

                $totals[$dimension->value] += (float) $score;
                $counts[$dimension->value]++;
            }
        }

        $rows = [];

        foreach (WritingDimension::cases() as $dimension) {
            if ($counts[$dimension->value] === 0) {
                continue;
            }

            $rows[] = [
                'dimension' => $dimension->value,
                'label' => $dimension->label(),
                'average_score' => round($totals[$dimension->value] / $counts[$dimension->value], 2),
                'sample_count' => $counts[$dimension->value],
            ];
        }

        usort(
            $rows,
            fn (array $left, array $right): int => $left['average_score'] <=> $right['average_score'],
        );

        return [
            'dimensions' => $rows,
            'sample_count' => $submissions->count(),
        ];
    }

    /**
     * @param  Collection<int, TutorViolation>  $violations
     * @return list<array{category: string, label: string, count: int}>
     */
    private function violationCategoryCounts(Collection $violations): array
    {
        $counts = [];

        foreach (TutorViolationCategory::cases() as $category) {
            $counts[$category->value] = 0;
        }

        foreach ($violations as $violation) {
            $counts[$violation->category->value]++;
        }

        $rows = [];

        foreach (TutorViolationCategory::cases() as $category) {
            if ($counts[$category->value] === 0) {
                continue;
            }

            $rows[] = [
                'category' => $category->value,
                'label' => $category->label(),
                'count' => $counts[$category->value],
            ];
        }

        usort($rows, fn (array $left, array $right): int => $right['count'] <=> $left['count']);

        return $rows;
    }
}
