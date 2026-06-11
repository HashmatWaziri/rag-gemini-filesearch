<?php

declare(strict_types=1);

use App\Models\Glc\Course;
use App\Models\Glc\CourseLesson;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');

    $this->supervisor = User::factory()->academicSupervisor()->create();
});

it('creates, renames, and deletes a course', function (): void {
    $this->actingAs($this->supervisor)
        ->post(route('curriculum.courses.store'), ['name' => 'General English'])
        ->assertRedirect();

    $course = Course::query()->where('name', 'General English')->firstOrFail();

    $this->actingAs($this->supervisor)
        ->put(route('curriculum.courses.update', $course), ['name' => 'General English 2026'])
        ->assertRedirect();

    expect($course->refresh()->name)->toBe('General English 2026');

    $this->actingAs($this->supervisor)
        ->delete(route('curriculum.courses.destroy', $course))
        ->assertRedirect();

    expect(Course::query()->find($course->id))->toBeNull();
});

it('creates levels, units, and lessons with auto-incrementing positions', function (): void {
    $course = Course::factory()->create();

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.levels.store'), ['course_id' => $course->id, 'name' => 'Starter'])
        ->assertRedirect();
    $this->actingAs($this->supervisor)
        ->post(route('curriculum.levels.store'), ['course_id' => $course->id, 'name' => 'Elementary'])
        ->assertRedirect();

    $starter = CourseLevel::query()->where('name', 'Starter')->firstOrFail();
    $elementary = CourseLevel::query()->where('name', 'Elementary')->firstOrFail();

    expect($starter->position)->toBe(1)
        ->and($elementary->position)->toBe(2);

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.units.store'), ['course_level_id' => $starter->id, 'name' => 'Unit 1'])
        ->assertRedirect();

    $unit = CourseUnit::query()->where('name', 'Unit 1')->firstOrFail();
    expect($unit->course_level_id)->toBe($starter->id);

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.lessons.store'), ['course_unit_id' => $unit->id, 'name' => 'Lesson 1'])
        ->assertRedirect();

    expect(CourseLesson::query()->where('course_unit_id', $unit->id)->where('name', 'Lesson 1')->exists())->toBeTrue();
});

it('updates name and position of levels, units, and lessons', function (): void {
    $level = CourseLevel::factory()->create(['position' => 1]);
    $unit = CourseUnit::factory()->create(['course_level_id' => $level->id, 'position' => 1]);
    $lesson = CourseLesson::factory()->create(['course_unit_id' => $unit->id, 'position' => 1]);

    $this->actingAs($this->supervisor)
        ->put(route('curriculum.levels.update', $level), ['name' => 'Renamed Level', 'position' => 5])
        ->assertRedirect();
    $this->actingAs($this->supervisor)
        ->put(route('curriculum.units.update', $unit), ['name' => 'Renamed Unit', 'position' => 3])
        ->assertRedirect();
    $this->actingAs($this->supervisor)
        ->put(route('curriculum.lessons.update', $lesson), ['name' => 'Renamed Lesson', 'position' => 2])
        ->assertRedirect();

    expect($level->refresh())
        ->name->toBe('Renamed Level')
        ->position->toBe(5)
        ->and($unit->refresh())
        ->name->toBe('Renamed Unit')
        ->position->toBe(3)
        ->and($lesson->refresh())
        ->name->toBe('Renamed Lesson')
        ->position->toBe(2);
});

it('rejects a level created without a valid course', function (): void {
    $this->actingAs($this->supervisor)
        ->post(route('curriculum.levels.store'), ['course_id' => 999, 'name' => 'Orphan'])
        ->assertSessionHasErrors('course_id');
});

it('cascades course deletion to levels, units, lessons, and documents', function (): void {
    $course = Course::factory()->create();
    $level = CourseLevel::factory()->for($course)->create();
    $unit = CourseUnit::factory()->create(['course_level_id' => $level->id]);
    $lesson = CourseLesson::factory()->create(['course_unit_id' => $unit->id]);
    $document = CurriculumDocument::factory()->create([
        'course_id' => $course->id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
        'course_lesson_id' => $lesson->id,
    ]);

    $this->actingAs($this->supervisor)
        ->delete(route('curriculum.courses.destroy', $course))
        ->assertRedirect();

    expect(Course::query()->find($course->id))->toBeNull()
        ->and(CourseLevel::query()->find($level->id))->toBeNull()
        ->and(CourseUnit::query()->find($unit->id))->toBeNull()
        ->and(CourseLesson::query()->find($lesson->id))->toBeNull()
        ->and(CurriculumDocument::query()->find($document->id))->toBeNull();
});

it('keeps documents when only their lesson is deleted', function (): void {
    $lesson = CourseLesson::factory()->create();
    $unit = CourseUnit::query()->findOrFail($lesson->course_unit_id);
    $level = CourseLevel::query()->findOrFail($unit->course_level_id);
    $document = CurriculumDocument::factory()->create([
        'course_id' => $level->course_id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
        'course_lesson_id' => $lesson->id,
    ]);

    $this->actingAs($this->supervisor)
        ->delete(route('curriculum.lessons.destroy', $lesson))
        ->assertRedirect();

    expect($document->refresh()->course_lesson_id)->toBeNull();
});
