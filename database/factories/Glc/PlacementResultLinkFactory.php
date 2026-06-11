<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementResultLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementResultLink>
 */
final class PlacementResultLinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement_attempt_id' => PlacementAttempt::factory()->submitted(),
            'token' => PlacementResultLink::generateToken(),
            'email_to' => fake()->safeEmail(),
            'expires_at' => now()->addDays(30),
            'sent_at' => now(),
            'sent_by' => null,
        ];
    }

    public function expired(): self
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDay()]);
    }
}
