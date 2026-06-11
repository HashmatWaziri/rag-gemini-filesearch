<?php

declare(strict_types=1);

namespace Tests\Fixtures\Glc;

use App\Models\Glc\Course;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\StudentAssignment;
use App\Models\User;

final class TutorScenario
{
    /**
     * @return array{
     *     student: User,
     *     teacher: User,
     *     assignment: StudentAssignment,
     *     course: Course,
     *     level: CourseLevel,
     *     unit: CourseUnit,
     * }
     */
    public static function assignedStudent(?User $teacher = null, ?User $student = null): array
    {
        $teacher ??= User::factory()->teacher()->create();
        $student ??= User::factory()->student()->create();

        $teacher->assignedStudents()->syncWithoutDetaching([$student->id]);

        $course = Course::factory()->create();
        $level = CourseLevel::factory()->for($course)->create();
        $unit = CourseUnit::factory()->for($level, 'level')->create();

        $assignment = StudentAssignment::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'course_level_id' => $level->id,
            'course_unit_id' => $unit->id,
            'assigned_by' => $teacher->id,
        ]);

        return [
            'student' => $student,
            'teacher' => $teacher,
            'assignment' => $assignment,
            'course' => $course,
            'level' => $level,
            'unit' => $unit,
        ];
    }
}
