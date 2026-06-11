<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\GlcLevel;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementScore>
 */
final class PlacementScoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement_attempt_id' => PlacementAttempt::factory(),
            'section_scores' => [
                'reading' => 66.67,
                'grammar_vocabulary' => 72.73,
                'listening' => 60.0,
                'writing' => 64.0,
                'speaking' => 56.0,
            ],
            'composite' => 63.88,
            'suggested_level' => GlcLevel::Intermediate,
            'variance_flagged' => false,
            'computed_at' => now(),
        ];
    }

    public function varianceFlagged(): self
    {
        return $this->state(fn (): array => [
            'section_scores' => [
                'reading' => 90.0,
                'grammar_vocabulary' => 85.0,
                'listening' => 30.0,
                'writing' => 64.0,
                'speaking' => 56.0,
            ],
            'composite' => 65.0,
            'variance_flagged' => true,
        ]);
    }
}
