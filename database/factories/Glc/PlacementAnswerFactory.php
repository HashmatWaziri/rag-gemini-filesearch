<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementAnswer>
 */
final class PlacementAnswerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement_attempt_id' => PlacementAttempt::factory(),
            'placement_item_id' => PlacementItem::factory(),
            'response' => ['selected' => 0],
            'is_correct' => null,
            'word_count' => null,
            'recording_attempts' => 0,
        ];
    }

    public function essay(): self
    {
        $text = fake()->sentences(12, true);

        return $this->state(fn (): array => [
            'response' => ['text' => $text],
            'word_count' => str_word_count($text),
        ]);
    }

    public function recording(): self
    {
        return $this->state(fn (): array => [
            'response' => [
                'audio_path' => 'glc/placement/recordings/sample.webm',
                'duration_seconds' => 95,
                'mime_type' => 'audio/webm',
            ],
            'recording_attempts' => 1,
        ]);
    }
}
