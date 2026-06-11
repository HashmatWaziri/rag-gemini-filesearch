<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\StudentAssignment;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
});

it('shows teachers only their linked students', function (): void {
    $teacher = User::factory()->teacher()->create();
    $linked = User::factory()->student()->create();
    User::factory()->student()->create();

    $teacher->assignedStudents()->attach($linked);

    actingAs($teacher)
        ->get(route('staff.students.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/staff/students')
            ->has('students', 1)
            ->where('students.0.id', $linked->id)
            ->where('canViewAll', false));
});

it('shows supervisors and admins all students', function (string $factoryState): void {
    $staff = User::factory()->{$factoryState}()->create();
    User::factory()->student()->count(3)->create();

    actingAs($staff)
        ->get(route('staff.students.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('students', 3)
            ->where('canViewAll', true));
})->with(['academicSupervisor', 'admin']);

it('exposes consent state per student', function (): void {
    $teacher = User::factory()->teacher()->create();
    $minor = User::factory()->minorStudent()->create();
    $teacher->assignedStudents()->attach($minor);

    actingAs($teacher)
        ->get(route('staff.students.index'))
        ->assertInertia(fn ($page) => $page
            ->where('students.0.consent.required', true)
            ->where('students.0.consent.confirmed', false));
});

it('provides the course hierarchy for dependent selects', function (): void {
    $course = Course::factory()->create();
    $level = CourseLevel::factory()->for($course)->create();
    CourseUnit::factory()->for($level, 'level')->count(2)->create();

    actingAs(User::factory()->admin()->create())
        ->get(route('staff.students.index'))
        ->assertInertia(fn ($page) => $page
            ->has('courses', 1)
            ->has('courses.0.levels', 1)
            ->has('courses.0.levels.0.units', 2));
});

it('lets a teacher link and unlink a student', function (): void {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    actingAs($teacher)->post(route('staff.students.link', $student))->assertRedirect();

    expect($teacher->assignedStudents()->whereKey($student->id)->exists())->toBeTrue();

    actingAs($teacher)->post(route('staff.students.link', $student))->assertRedirect();
    expect($teacher->assignedStudents()->count())->toBe(1);

    actingAs($teacher)->delete(route('staff.students.unlink', $student))->assertRedirect();
    expect($teacher->assignedStudents()->whereKey($student->id)->exists())->toBeFalse();
});

it('rejects linking a non-student account', function (): void {
    $teacher = User::factory()->teacher()->create();
    $otherTeacher = User::factory()->teacher()->create();

    actingAs($teacher)->post(route('staff.students.link', $otherTeacher))->assertNotFound();
});

it('lets a teacher set an assignment for a linked student and audits it', function (): void {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $teacher->assignedStudents()->attach($student);

    $course = Course::factory()->create();
    $level = CourseLevel::factory()->for($course)->create();
    $unit = CourseUnit::factory()->for($level, 'level')->create();

    actingAs($teacher)
        ->put(route('staff.students.assignment.update', $student), [
            'course_id' => $course->id,
            'course_level_id' => $level->id,
            'course_unit_id' => $unit->id,
        ])
        ->assertRedirect();

    $assignment = StudentAssignment::query()->where('student_id', $student->id)->sole();

    expect($assignment->course_id)->toBe($course->id)
        ->and($assignment->course_level_id)->toBe($level->id)
        ->and($assignment->course_unit_id)->toBe($unit->id)
        ->and($assignment->assigned_by)->toBe($teacher->id);

    $log = AuditLog::query()->where('action', AuditAction::StudentAssigned->value)->sole();

    expect($log->actor_id)->toBe($teacher->id)
        ->and($log->details)->toMatchArray([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'course_level_id' => $level->id,
            'course_unit_id' => $unit->id,
        ]);
});

it('upserts the assignment per student instead of duplicating', function (): void {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $teacher->assignedStudents()->attach($student);

    $courseA = Course::factory()->create();
    $levelA = CourseLevel::factory()->for($courseA)->create();
    $unitA = CourseUnit::factory()->for($levelA, 'level')->create();

    $courseB = Course::factory()->create();
    $levelB = CourseLevel::factory()->for($courseB)->create();
    $unitB = CourseUnit::factory()->for($levelB, 'level')->create();

    actingAs($teacher)->put(route('staff.students.assignment.update', $student), [
        'course_id' => $courseA->id,
        'course_level_id' => $levelA->id,
        'course_unit_id' => $unitA->id,
    ]);

    actingAs($teacher)->put(route('staff.students.assignment.update', $student), [
        'course_id' => $courseB->id,
        'course_level_id' => $levelB->id,
        'course_unit_id' => $unitB->id,
    ]);

    expect(StudentAssignment::query()->where('student_id', $student->id)->count())->toBe(1)
        ->and(StudentAssignment::query()->where('student_id', $student->id)->sole()->course_id)->toBe($courseB->id);
});

it('blocks teachers from assigning unlinked students', function (): void {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    $course = Course::factory()->create();
    $level = CourseLevel::factory()->for($course)->create();
    $unit = CourseUnit::factory()->for($level, 'level')->create();

    actingAs($teacher)
        ->put(route('staff.students.assignment.update', $student), [
            'course_id' => $course->id,
            'course_level_id' => $level->id,
            'course_unit_id' => $unit->id,
        ])
        ->assertForbidden();
});

it('lets supervisors assign any student without linking', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $student = User::factory()->student()->create();

    $course = Course::factory()->create();
    $level = CourseLevel::factory()->for($course)->create();
    $unit = CourseUnit::factory()->for($level, 'level')->create();

    actingAs($supervisor)
        ->put(route('staff.students.assignment.update', $student), [
            'course_id' => $course->id,
            'course_level_id' => $level->id,
            'course_unit_id' => $unit->id,
        ])
        ->assertRedirect();

    expect(StudentAssignment::query()->where('student_id', $student->id)->exists())->toBeTrue();
});

it('rejects a level that does not belong to the chosen course', function (): void {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();

    $course = Course::factory()->create();
    CourseLevel::factory()->for($course)->create();

    $otherCourse = Course::factory()->create();
    $foreignLevel = CourseLevel::factory()->for($otherCourse)->create();
    $foreignUnit = CourseUnit::factory()->for($foreignLevel, 'level')->create();

    actingAs($admin)
        ->put(route('staff.students.assignment.update', $student), [
            'course_id' => $course->id,
            'course_level_id' => $foreignLevel->id,
            'course_unit_id' => $foreignUnit->id,
        ])
        ->assertSessionHasErrors('course_level_id');
});

it('blocks students from the staff students screen', function (): void {
    actingAs(User::factory()->student()->create())
        ->get(route('staff.students.index'))
        ->assertForbidden();
});
