<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementAudioPlay;
use App\Models\Glc\PlacementItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementAudioPlay>
 */
final class PlacementAudioPlayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement_attempt_id' => PlacementAttempt::factory(),
            'placement_item_id' => PlacementItem::factory()->audioClip(),
            'played_at' => now(),
        ];
    }
}
