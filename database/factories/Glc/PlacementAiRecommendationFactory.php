<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementAiDraftStatus;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAiRecommendation;
use App\Models\Glc\PlacementAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementAiRecommendation>
 */
final class PlacementAiRecommendationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $skillLevels = [];
        $skillSummaries = [];

        foreach (PlacementSection::ordered() as $section) {
            $skillLevels[$section->value] = GlcLevel::Intermediate->value;
            $skillSummaries[$section->value] = fake()->sentence(12);
        }

        return [
            'placement_attempt_id' => PlacementAttempt::factory(),
            'status' => PlacementAiDraftStatus::Completed,
            'recommended_level' => GlcLevel::Intermediate,
            'skill_levels' => $skillLevels,
            'skill_summaries' => $skillSummaries,
            'confidence' => 'medium',
            'rationale' => fake()->paragraph(),
            'error' => null,
            'generated_at' => now(),
        ];
    }

    public function failed(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementAiDraftStatus::Failed,
            'recommended_level' => null,
            'skill_levels' => null,
            'skill_summaries' => null,
            'confidence' => null,
            'rationale' => null,
            'error' => 'Provider error',
            'generated_at' => null,
        ]);
    }
}
