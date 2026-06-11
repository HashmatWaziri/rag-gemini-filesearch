<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\Course;
use App\Models\Glc\CourseLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseLevel>
 */
final class CourseLevelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'name' => fake()->randomElement(['Starter', 'Elementary', 'Intermediate', 'Advanced']),
            'position' => 0,
        ];
    }
}
