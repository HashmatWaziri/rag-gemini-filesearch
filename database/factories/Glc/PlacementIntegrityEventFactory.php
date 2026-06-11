<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\PlacementIntegrityEventType;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementIntegrityEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementIntegrityEvent>
 */
final class PlacementIntegrityEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement_attempt_id' => PlacementAttempt::factory(),
            'type' => PlacementIntegrityEventType::TabSwitch,
            'metadata' => null,
            'occurred_at' => now(),
        ];
    }

    public function dualDevice(): self
    {
        return $this->state(fn (): array => ['type' => PlacementIntegrityEventType::DualDevice]);
    }

    public function pasteAttempt(): self
    {
        return $this->state(fn (): array => ['type' => PlacementIntegrityEventType::PasteAttempt]);
    }
}
