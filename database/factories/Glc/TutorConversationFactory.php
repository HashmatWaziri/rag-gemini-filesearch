<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\TutorConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TutorConversation>
 */
final class TutorConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'title' => fake()->sentence(4),
            'summary' => null,
            'last_activity_at' => now(),
        ];
    }
}
