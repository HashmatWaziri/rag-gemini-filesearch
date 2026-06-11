<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\PlacementAiDraftStatus;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAiDraft;
use App\Models\Glc\PlacementAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementAiDraft>
 */
final class PlacementAiDraftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement_attempt_id' => PlacementAttempt::factory(),
            'section' => PlacementSection::Writing,
            'dimension_scores' => [
                'grammar' => 3,
                'vocabulary' => 3,
                'structure' => 4,
                'coherence' => 3,
                'task_completion' => 4,
            ],
            'transcript' => null,
            'feedback' => fake()->paragraph(),
            'confidence' => 'medium',
            'status' => PlacementAiDraftStatus::Completed,
            'generated_at' => now(),
        ];
    }

    public function speaking(): self
    {
        return $this->state(fn (): array => [
            'section' => PlacementSection::Speaking,
            'transcript' => fake()->paragraph(),
        ]);
    }

    public function pending(): self
    {
        return $this->state(fn (): array => [
            'dimension_scores' => null,
            'feedback' => null,
            'confidence' => null,
            'status' => PlacementAiDraftStatus::Pending,
            'generated_at' => null,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (): array => [
            'dimension_scores' => null,
            'feedback' => null,
            'confidence' => null,
            'status' => PlacementAiDraftStatus::Failed,
            'error' => 'Provider error',
            'generated_at' => null,
        ]);
    }
}
