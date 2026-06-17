<?php

declare(strict_types=1);

use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Storage::fake('local');
    $this->withoutVite();
});

it('forbids teachers from uploading curriculum documents', function (): void {
    $teacher = User::factory()->teacher()->create();
    $unit = CourseUnit::factory()->create();
    $level = App\Models\Glc\CourseLevel::query()->findOrFail($unit->course_level_id);

    $this->actingAs($teacher)
        ->post(route('curriculum.documents.store'), [
            'title' => 'Blocked upload',
            'material_kind' => 'worksheet',
            'course_id' => $level->course_id,
            'course_level_id' => $level->id,
            'course_unit_id' => $unit->id,
            'file' => UploadedFile::fake()->createWithContent('notes.txt', 'content'),
        ])
        ->assertForbidden();
});

it('allows admins to delete curriculum documents', function (): void {
    $admin = User::factory()->admin()->create();
    $document = CurriculumDocument::factory()->create([
        'file_path' => 'glc/curriculum/1/delete-me.txt',
    ]);

    Storage::disk('local')->put($document->file_path, 'delete me');

    $this->actingAs($admin)
        ->delete(route('curriculum.documents.destroy', $document))
        ->assertRedirect(route('curriculum.index'));

    expect(CurriculumDocument::query()->find($document->id))->toBeNull();
});

it('forbids academic supervisors from deleting curriculum documents', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $document = CurriculumDocument::factory()->create();

    $this->actingAs($supervisor)
        ->delete(route('curriculum.documents.destroy', $document))
        ->assertForbidden();

    expect(CurriculumDocument::query()->find($document->id))->not->toBeNull();
});

it('allows academic supervisors to publish curriculum documents', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $document = CurriculumDocument::factory()->create([
        'file_path' => 'glc/curriculum/1/draft.txt',
    ]);

    Storage::disk('local')->put($document->file_path, 'Draft content.');

    $this->actingAs($supervisor)
        ->post(route('curriculum.documents.publish', $document), [
            'preview_confirmed' => true,
        ])
        ->assertRedirect();
});

it('respects spatie role permission changes', function (): void {
    $teacher = User::factory()->teacher()->create();
    $unit = CourseUnit::factory()->create();
    $level = App\Models\Glc\CourseLevel::query()->findOrFail($unit->course_level_id);

    $this->actingAs($teacher)
        ->get(route('curriculum.index'))
        ->assertForbidden();

    Role::findByName('teacher', 'web')->givePermissionTo([
        'curriculum_view',
        'curriculum_upload',
    ]);

    $teacher->forgetCachedPermissions();

    $this->actingAs($teacher)
        ->get(route('curriculum.index'))
        ->assertOk();

    $this->actingAs($teacher)
        ->post(route('curriculum.documents.store'), [
            'title' => 'Teacher upload',
            'material_kind' => 'worksheet',
            'course_id' => $level->course_id,
            'course_level_id' => $level->id,
            'course_unit_id' => $unit->id,
            'file' => UploadedFile::fake()->createWithContent('notes.txt', 'content'),
        ])
        ->assertRedirect();
});
