<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\TutorUsageDaily;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TutorUsageDaily>
 */
final class TutorUsageDailyFactory extends Factory
{
    protected $model = TutorUsageDaily::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'date' => now()->toDateString(),
            'active_minutes' => fake()->numberBetween(5, 45),
            'message_count' => fake()->numberBetween(1, 20),
            'conversation_starts' => fake()->numberBetween(0, 3),
        ];
    }
}
