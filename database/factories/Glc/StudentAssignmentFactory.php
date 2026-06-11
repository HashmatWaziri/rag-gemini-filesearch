<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\Course;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\StudentAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentAssignment>
 */
final class StudentAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'course_id' => Course::factory(),
            'course_level_id' => CourseLevel::factory(),
            'course_unit_id' => CourseUnit::factory(),
            'assigned_by' => User::factory()->teacher(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (StudentAssignment $assignment): void {
            $level = CourseLevel::query()->find($assignment->course_level_id);

            if ($level !== null && $assignment->course_id !== $level->course_id) {
                $assignment->course_id = $level->course_id;
            }

            $unit = CourseUnit::query()->find($assignment->course_unit_id);

            if ($unit !== null && $unit->course_level_id !== $assignment->course_level_id) {
                $unit->update(['course_level_id' => $assignment->course_level_id]);
            }
        });
    }
}
