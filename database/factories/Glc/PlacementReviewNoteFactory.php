<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\PlacementReview;
use App\Models\Glc\PlacementReviewNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementReviewNote>
 */
final class PlacementReviewNoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'placement_review_id' => PlacementReview::factory(),
            'author_id' => User::factory(),
            'note' => fake()->sentence(),
        ];
    }
}
