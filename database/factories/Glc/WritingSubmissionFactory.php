<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\WritingSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WritingSubmission>
 */
final class WritingSubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'tutor_conversation_id' => null,
            'text' => fake()->paragraphs(2, true),
            'feedback' => null,
            'highlights' => null,
            'status' => 'pending',
        ];
    }

    public function completed(): self
    {
        return $this->state(fn (): array => [
            'status' => 'completed',
            'feedback' => [
                'dimensions' => [
                    'grammar' => ['score' => 3, 'comment' => 'Some tense errors.'],
                    'vocabulary' => ['score' => 4, 'comment' => 'Good range of words.'],
                    'structure' => ['score' => 3, 'comment' => 'Paragraphing needs work.'],
                    'coherence' => ['score' => 4, 'comment' => 'Ideas flow logically.'],
                    'task_completion' => ['score' => 4, 'comment' => 'Addresses the task.'],
                ],
                'summary' => 'Solid attempt with room to improve grammar accuracy.',
            ],
            'highlights' => [
                ['start' => 0, 'end' => 12, 'type' => 'grammar', 'comment' => 'Check verb tense.'],
            ],
        ]);
    }
}
