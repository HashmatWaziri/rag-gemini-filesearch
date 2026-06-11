<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseUnit>
 */
final class CourseUnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_level_id' => CourseLevel::factory(),
            'name' => 'Unit '.fake()->numberBetween(1, 12),
            'position' => 0,
        ];
    }
}
