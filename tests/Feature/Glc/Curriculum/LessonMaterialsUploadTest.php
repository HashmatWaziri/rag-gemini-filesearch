<?php

declare(strict_types=1);

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumMaterialKind;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLesson;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');

    $this->supervisor = User::factory()->academicSupervisor()->create();
    $this->course = Course::factory()->create();
    $this->level = CourseLevel::factory()->for($this->course)->create();
    $this->unit = CourseUnit::factory()->create(['course_level_id' => $this->level->id]);
    $this->lesson = CourseLesson::factory()->create(['course_unit_id' => $this->unit->id]);

    $this->tags = [
        'course_id' => $this->course->id,
        'course_level_id' => $this->level->id,
        'course_unit_id' => $this->unit->id,
        'course_lesson_id' => $this->lesson->id,
    ];
});

it('uploads multiple material types for one lesson in a single request', function (): void {
    $response = $this->actingAs($this->supervisor)->post(route('curriculum.documents.lesson-materials'), [
        ...$this->tags,
        'material_kinds' => [
            CurriculumMaterialKind::Summary->value,
            CurriculumMaterialKind::Notes->value,
            CurriculumMaterialKind::Worksheet->value,
        ],
        'titles' => ['Lesson summary', '', 'Practice sheet'],
        'files' => [
            UploadedFile::fake()->createWithContent('summary.txt', 'Summary body'),
            UploadedFile::fake()->createWithContent('notes.txt', 'Notes body'),
            UploadedFile::fake()->createWithContent('worksheet.txt', 'Worksheet body'),
        ],
    ]);

    $response->assertRedirect(route('curriculum.index', [
        'course_id' => $this->course->id,
        'course_level_id' => $this->level->id,
        'course_unit_id' => $this->unit->id,
        'course_lesson_id' => $this->lesson->id,
    ]));

    expect(CurriculumDocument::query()->count())->toBe(3);

    $summary = CurriculumDocument::query()->where('title', 'Lesson summary')->firstOrFail();
    $notes = CurriculumDocument::query()->where('title', 'notes')->firstOrFail();
    $worksheet = CurriculumDocument::query()->where('title', 'Practice sheet')->firstOrFail();

    expect($summary)
        ->material_kind->toBe(CurriculumMaterialKind::Summary)
        ->course_lesson_id->toBe($this->lesson->id)
        ->status->toBe(CurriculumDocumentStatus::Draft);

    expect($notes->material_kind)->toBe(CurriculumMaterialKind::Notes);
    expect($worksheet->material_kind)->toBe(CurriculumMaterialKind::Worksheet);
});

it('requires a lesson when uploading lesson materials', function (): void {
    $this->actingAs($this->supervisor)->post(route('curriculum.documents.lesson-materials'), [
        'course_id' => $this->course->id,
        'course_level_id' => $this->level->id,
        'course_unit_id' => $this->unit->id,
        'material_kinds' => [CurriculumMaterialKind::Summary->value],
        'files' => [UploadedFile::fake()->createWithContent('summary.txt', 'Summary')],
    ])->assertSessionHasErrors('course_lesson_id');

    expect(CurriculumDocument::query()->count())->toBe(0);
});

it('rejects mismatched material and file counts', function (): void {
    $this->actingAs($this->supervisor)->post(route('curriculum.documents.lesson-materials'), [
        ...$this->tags,
        'material_kinds' => [CurriculumMaterialKind::Summary->value],
        'files' => [
            UploadedFile::fake()->createWithContent('one.txt', 'one'),
            UploadedFile::fake()->createWithContent('two.txt', 'two'),
        ],
    ])->assertSessionHasErrors('files');

    expect(CurriculumDocument::query()->count())->toBe(0);
});
