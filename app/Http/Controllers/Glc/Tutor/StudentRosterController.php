<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Tutor;

use App\Enums\Glc\UserRole;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\StudentAssignment;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class StudentRosterController
{
    public function __construct(#[CurrentUser] private User $user) {}

    public function index(): Response
    {
        $canViewAll = $this->user->role instanceof UserRole && $this->user->role->canViewAllStudents();

        $students = ($canViewAll
            ? User::query()->where('role', UserRole::Student)
            : $this->user->assignedStudents())
            ->with(['studentAssignment.course', 'studentAssignment.level', 'studentAssignment.unit'])
            ->orderBy('name')
            ->get();

        /** @var list<int> $linkedIds */
        $linkedIds = $this->user->assignedStudents()->pluck('users.id')->all();

        $linkableStudents = User::query()
            ->where('role', UserRole::Student)
            ->whereNotIn('id', $linkedIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ])
            ->all();

        return Inertia::render('glc/tutor/staff/students', [
            'students' => $students
                ->map(fn (User $student): array => $this->studentPayload($student, $linkedIds))
                ->all(),
            'linkableStudents' => $linkableStudents,
            'courses' => $this->coursesPayload(),
            'canViewAll' => $canViewAll,
        ]);
    }

    /**
     * @param  list<int>  $linkedIds
     * @return array<string, mixed>
     */
    private function studentPayload(User $student, array $linkedIds): array
    {
        $assignment = $student->studentAssignment;

        return [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'age' => $student->age,
            'linked' => in_array($student->id, $linkedIds, true),
            'consent' => [
                'required' => $student->requiresGuardianConsent(),
                'confirmed' => $student->hasGuardianConsent(),
            ],
            'assignment' => $assignment instanceof StudentAssignment ? [
                'course_id' => $assignment->course_id,
                'course_level_id' => $assignment->course_level_id,
                'course_unit_id' => $assignment->course_unit_id,
                'course' => $assignment->course->name,
                'level' => $assignment->level->name,
                'unit' => $assignment->unit->name,
            ] : null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function coursesPayload(): array
    {
        return Course::query()
            ->with('levels.units')
            ->orderBy('name')
            ->get()
            ->map(fn (Course $course): array => [
                'id' => $course->id,
                'name' => $course->name,
                'levels' => $course->levels->map(fn (CourseLevel $level): array => [
                    'id' => $level->id,
                    'name' => $level->name,
                    'units' => $level->units->map(fn (CourseUnit $unit): array => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                    ])->all(),
                ])->all(),
            ])
            ->all();
    }
}
