<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementItem>
 */
final class PlacementItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section' => PlacementSection::Reading,
            'type' => PlacementItemType::Question,
            'parent_id' => null,
            'position' => 0,
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
            'correct_option' => 0,
            'media_path' => null,
            'settings' => null,
            'is_active' => true,
        ];
    }

    public function passage(): self
    {
        return $this->state(fn (): array => [
            'section' => PlacementSection::Reading,
            'type' => PlacementItemType::Passage,
            'body' => fake()->paragraphs(3, true),
            'options' => null,
            'correct_option' => null,
        ]);
    }

    public function audioClip(): self
    {
        return $this->state(fn (): array => [
            'section' => PlacementSection::Listening,
            'type' => PlacementItemType::AudioClip,
            'body' => null,
            'options' => null,
            'correct_option' => null,
            'media_path' => 'glc/placement/audio/clip.mp3',
        ]);
    }

    public function writingPrompt(): self
    {
        return $this->state(fn (): array => [
            'section' => PlacementSection::Writing,
            'type' => PlacementItemType::Prompt,
            'body' => 'Write an essay about your favorite holiday.',
            'options' => null,
            'correct_option' => null,
            'settings' => ['min_words' => 150, 'max_words' => 250],
        ]);
    }

    public function speakingPrompt(): self
    {
        return $this->state(fn (): array => [
            'section' => PlacementSection::Speaking,
            'type' => PlacementItemType::Prompt,
            'body' => 'Describe your hometown and what you like about it.',
            'options' => null,
            'correct_option' => null,
            'settings' => ['max_duration_seconds' => 180, 'max_attempts' => 3],
        ]);
    }

    public function forSection(PlacementSection $section): self
    {
        return $this->state(fn (): array => ['section' => $section]);
    }
}
