<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementSectionState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementSectionState>
 */
final class PlacementSectionStateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement_attempt_id' => PlacementAttempt::factory(),
            'section' => PlacementSection::Reading,
            'status' => PlacementSectionStatus::Locked,
            'time_limit_seconds' => 900,
            'time_used_seconds' => 0,
        ];
    }

    public function forSection(PlacementSection $section): self
    {
        return $this->state(fn (): array => [
            'section' => $section,
            'time_limit_seconds' => $section->timeLimitSeconds(),
        ]);
    }

    public function inProgress(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementSectionStatus::InProgress,
            'started_at' => now(),
            'last_resumed_at' => now(),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementSectionStatus::Completed,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
        ]);
    }
}
