<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementReviewStatus;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementReview>
 */
final class PlacementReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement_attempt_id' => PlacementAttempt::factory()->submitted(),
            'assigned_to' => null,
            'status' => PlacementReviewStatus::Pending,
            'final_level' => null,
            'skill_levels' => null,
            'narrative' => null,
            'flags' => null,
        ];
    }

    public function approved(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementReviewStatus::Approved,
            'final_level' => GlcLevel::Intermediate,
            'skill_levels' => [
                'reading' => GlcLevel::Intermediate->value,
                'grammar_vocabulary' => GlcLevel::Intermediate->value,
                'listening' => GlcLevel::PreIntermediate->value,
                'writing' => GlcLevel::Intermediate->value,
                'speaking' => GlcLevel::PreIntermediate->value,
            ],
            'narrative' => [
                'strengths' => 'Reads confidently and uses a fair range of vocabulary.',
                'areas_to_improve' => 'Listening accuracy and spoken fluency need support.',
                'recommendation' => 'Place in Intermediate with listening reinforcement.',
                'next_steps' => 'Enroll in the Intermediate course; review unit audio weekly.',
            ],
            'narrative_approved_at' => now(),
            'approved_at' => now(),
        ]);
    }

    public function flagged(): self
    {
        return $this->state(fn (): array => ['flags' => ['variance']]);
    }
}
