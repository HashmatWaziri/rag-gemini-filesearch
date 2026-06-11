<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\PlacementAttemptStatus;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAccessCode;
use App\Models\Glc\PlacementAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PlacementAttempt>
 */
final class PlacementAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement_access_code_id' => PlacementAccessCode::factory(),
            'candidate_name' => fake()->name(),
            'candidate_email' => fake()->unique()->safeEmail(),
            'candidate_age' => fake()->numberBetween(12, 45),
            'status' => PlacementAttemptStatus::InProgress,
            'device_token' => Str::random(64),
            'current_section' => PlacementSection::Reading,
            'privacy_acknowledged_at' => now(),
            'instructions_acknowledged_at' => now(),
            'last_activity_at' => now(),
            'started_at' => now(),
        ];
    }

    public function submitted(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementAttemptStatus::Submitted,
            'current_section' => null,
            'submitted_at' => now(),
        ]);
    }

    public function terminated(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementAttemptStatus::Terminated,
            'terminated_at' => now(),
            'termination_reason' => 'dual_device',
        ]);
    }

    public function minor(): self
    {
        return $this->state(fn (): array => ['candidate_age' => fake()->numberBetween(12, 17)]);
    }
}
