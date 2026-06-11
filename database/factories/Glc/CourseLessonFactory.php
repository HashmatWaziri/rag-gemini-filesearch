<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\CourseLesson;
use App\Models\Glc\CourseUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseLesson>
 */
final class CourseLessonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_unit_id' => CourseUnit::factory(),
            'name' => 'Lesson '.fake()->numberBetween(1, 8),
            'position' => 0,
        ];
    }
}
