<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Tutor;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\UserRole;
use App\Models\Glc\StudentAssignment;
use App\Models\User;
use App\Services\Glc\AuditLogger;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final readonly class StudentAssignmentController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private AuditLogger $audit,
    ) {}

    public function update(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->isGlcStudent(), 404);

        if ($this->user->role === UserRole::Teacher
            && ! $this->user->assignedStudents()->whereKey($student->id)->exists()) {
            abort(403);
        }

        /** @var array{course_id: int, course_level_id: int, course_unit_id: int} $validated */
        $validated = $request->validate([
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'course_level_id' => [
                'required',
                'integer',
                Rule::exists('course_levels', 'id')->where('course_id', $request->integer('course_id')),
            ],
            'course_unit_id' => [
                'required',
                'integer',
                Rule::exists('course_units', 'id')->where('course_level_id', $request->integer('course_level_id')),
            ],
        ]);

        $assignment = StudentAssignment::query()->updateOrCreate(
            ['student_id' => $student->id],
            [...$validated, 'assigned_by' => $this->user->id],
        );

        $assignment->load(['course', 'level', 'unit']);

        $this->audit->log(AuditAction::StudentAssigned, $this->user, $assignment, [
            'student_id' => $student->id,
            'student_name' => $student->name,
            'course_id' => $assignment->course_id,
            'course' => $assignment->course->name,
            'course_level_id' => $assignment->course_level_id,
            'level' => $assignment->level->name,
            'course_unit_id' => $assignment->course_unit_id,
            'unit' => $assignment->unit->name,
        ]);

        return back();
    }
}
