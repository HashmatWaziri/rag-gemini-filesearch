<?php

declare(strict_types=1);

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\StudentAssignment;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
});

it('renders the tutor setup wizard for staff', function (): void {
    actingAs(User::factory()->academicSupervisor()->create())
        ->get(route('staff.tutor-setup.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/staff/setup')
            ->where('canManageCurriculum', true)
            ->has('courses')
            ->has('students'));
});

it('retains partial scope selection from query params', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $course = Course::factory()->create();
    CourseLevel::factory()->for($course)->count(2)->create();

    actingAs($supervisor)
        ->get(route('staff.tutor-setup.index', ['course_id' => $course->id]))
        ->assertInertia(fn ($page) => $page
            ->where('scope', null)
            ->where('scopeSelection.course_id', $course->id)
            ->where('scopeSelection.course_level_id', null)
            ->where('scopeSelection.course_unit_id', null));
});

it('retains course and level when only those query params are present', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $course = Course::factory()->create();
    $level = CourseLevel::factory()->for($course)->create();
    CourseUnit::factory()->for($level, 'level')->count(2)->create();

    actingAs($supervisor)
        ->get(route('staff.tutor-setup.index', [
            'course_id' => $course->id,
            'course_level_id' => $level->id,
        ]))
        ->assertInertia(fn ($page) => $page
            ->where('scopeSelection.course_id', $course->id)
            ->where('scopeSelection.course_level_id', $level->id)
            ->where('scopeSelection.course_unit_id', null));
});

it('reports materials readiness for a scoped unit', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $course = Course::factory()->create();
    $level = CourseLevel::factory()->for($course)->create();
    $unit = CourseUnit::factory()->for($level, 'level')->create();

    CurriculumDocument::factory()->create([
        'course_id' => $course->id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
        'status' => CurriculumDocumentStatus::Published,
        'index_status' => CurriculumIndexStatus::Indexed,
    ]);

    actingAs($supervisor)
        ->get(route('staff.tutor-setup.index', [
            'course_id' => $course->id,
            'course_level_id' => $level->id,
            'course_unit_id' => $unit->id,
        ]))
        ->assertInertia(fn ($page) => $page
            ->where('materials.ready', true)
            ->where('materials.published_count', 1)
            ->where('scope.unit', $unit->name));
});

it('marks setup complete when materials are live and the student is assigned', function (): void {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $teacher->assignedStudents()->attach($student);

    $course = Course::factory()->create();
    $level = CourseLevel::factory()->for($course)->create();
    $unit = CourseUnit::factory()->for($level, 'level')->create();

    CurriculumDocument::factory()->create([
        'course_id' => $course->id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
        'status' => CurriculumDocumentStatus::Published,
        'index_status' => CurriculumIndexStatus::Indexed,
    ]);

    StudentAssignment::factory()->create([
        'student_id' => $student->id,
        'course_id' => $course->id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
        'assigned_by' => $teacher->id,
    ]);

    actingAs($teacher)
        ->get(route('staff.tutor-setup.index', [
            'course_id' => $course->id,
            'course_level_id' => $level->id,
            'course_unit_id' => $unit->id,
            'student_ids' => [$student->id],
        ]))
        ->assertInertia(fn ($page) => $page
            ->where('setupComplete', true)
            ->where('assignmentMatchesScope', true)
            ->where('selectedStudentIds', [$student->id])
            ->where('canManageCurriculum', false));
});

it('accepts legacy single student_id query param', function (): void {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $teacher->assignedStudents()->attach($student);

    actingAs($teacher)
        ->get(route('staff.tutor-setup.index', [
            'student_id' => $student->id,
        ]))
        ->assertInertia(fn ($page) => $page
            ->where('selectedStudentIds', [$student->id]));
});

it('assigns multiple students to a unit in one request', function (): void {
    $teacher = User::factory()->teacher()->create();
    $students = User::factory()->student()->count(2)->create();
    $teacher->assignedStudents()->attach($students);

    $course = Course::factory()->create();
    $level = CourseLevel::factory()->for($course)->create();
    $unit = CourseUnit::factory()->for($level, 'level')->create();

    actingAs($teacher)
        ->from(route('staff.tutor-setup.index'))
        ->put(route('staff.students.assignment.bulk'), [
            'student_ids' => $students->pluck('id')->all(),
            'course_id' => $course->id,
            'course_level_id' => $level->id,
            'course_unit_id' => $unit->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    foreach ($students as $student) {
        expect($student->fresh()->studentAssignment)
            ->not->toBeNull()
            ->course_unit_id->toBe($unit->id);
    }
});

it('denies tutor setup to students', function (): void {
    actingAs(User::factory()->student()->create())
        ->get(route('staff.tutor-setup.index'))
        ->assertForbidden();
});
