<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\TutorViolationCategory;
use App\Models\Glc\TutorViolation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TutorViolation>
 */
final class TutorViolationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'tutor_conversation_id' => null,
            'tutor_message_id' => null,
            'category' => TutorViolationCategory::DirectAnswerSeeking,
            'excerpt' => fake()->sentence(),
            'occurred_at' => now(),
        ];
    }

    public function category(TutorViolationCategory $category): self
    {
        return $this->state(fn (): array => ['category' => $category]);
    }
}
