<?php

declare(strict_types=1);

use App\Models\Glc\Course;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    $this->withoutVite();
});

it('redirects guests to login', function (): void {
    $this->get(route('curriculum.index'))->assertRedirectToRoute('login');
});

it('blocks teachers and students from the curriculum index', function (string $factoryState): void {
    $user = User::factory()->{$factoryState}()->create();

    $this->actingAs($user)
        ->get(route('curriculum.index'))
        ->assertForbidden();
})->with(['teacher', 'student']);

it('blocks teachers and students from uploading curriculum', function (string $factoryState): void {
    $user = User::factory()->{$factoryState}()->create();
    $unit = CourseUnit::factory()->create();
    $level = CourseLevel::query()->findOrFail($unit->course_level_id);

    $this->actingAs($user)
        ->post(route('curriculum.documents.store'), [
            'title' => 'Blocked upload',
            'course_id' => $level->course_id,
            'course_level_id' => $level->id,
            'course_unit_id' => $unit->id,
            'file' => UploadedFile::fake()->createWithContent('notes.txt', 'content'),
        ])
        ->assertForbidden();
})->with(['teacher', 'student']);

it('blocks teachers and students from hierarchy management', function (string $factoryState): void {
    $user = User::factory()->{$factoryState}()->create();

    $this->actingAs($user)
        ->post(route('curriculum.courses.store'), ['name' => 'Blocked'])
        ->assertForbidden();

    expect(Course::query()->where('name', 'Blocked')->exists())->toBeFalse();
})->with(['teacher', 'student']);

it('allows academic supervisors and admins to view the curriculum index', function (string $factoryState): void {
    $user = User::factory()->{$factoryState}()->create();

    $this->actingAs($user)
        ->get(route('curriculum.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/curriculum/index')
            ->has('documents')
            ->has('tree')
            ->has('upload'));
})->with(['academicSupervisor', 'admin']);
