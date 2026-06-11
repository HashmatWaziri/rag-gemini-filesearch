<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TutorMessage>
 */
final class TutorMessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tutor_conversation_id' => TutorConversation::factory(),
            'role' => 'user',
            'content' => fake()->sentence(),
            'metadata' => null,
        ];
    }

    public function assistant(): self
    {
        return $this->state(fn (): array => ['role' => 'assistant']);
    }
}
