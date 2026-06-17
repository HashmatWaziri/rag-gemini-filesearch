<?php

declare(strict_types=1);

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Enums\Glc\CurriculumMaterialKind;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLesson;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
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
    ];
});

it('stores an uploaded file as a draft without extracting text locally', function (): void {
    Queue::fake();

    $response = $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'course_lesson_id' => $this->lesson->id,
        'title' => 'Unit notes',
        'material_kind' => CurriculumMaterialKind::Notes->value,
        'file' => UploadedFile::fake()->createWithContent('week-1-notes.txt', 'GLC summary: present simple routines.'),
    ]);

    $document = CurriculumDocument::query()->firstOrFail();
    $response->assertRedirect(route('curriculum.documents.show', $document));

    expect($document)
        ->title->toBe('Unit notes')
        ->course_id->toBe($this->course->id)
        ->course_level_id->toBe($this->level->id)
        ->course_unit_id->toBe($this->unit->id)
        ->course_lesson_id->toBe($this->lesson->id)
        ->format->toBe('txt')
        ->original_filename->toBe('week-1-notes.txt')
        ->extracted_text->toBeNull()
        ->status->toBe(CurriculumDocumentStatus::Draft)
        ->index_status->toBe(CurriculumIndexStatus::Pending)
        ->version->toBe(1)
        ->uploaded_by->toBe($this->supervisor->id)
        ->material_kind->toBe(CurriculumMaterialKind::Notes);

    expect(str_starts_with($document->file_path, 'glc/curriculum/'.$this->course->id.'/'))->toBeTrue();
    Storage::disk('local')->assertExists($document->file_path);
    expect(Storage::disk('local')->get($document->file_path))
        ->toBe('GLC summary: present simple routines.');

    Queue::assertNothingPushed();
});

it('stores PDF and DOCX uploads without local text extraction', function (string $filename, string $contents): void {
    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'title' => 'Reading passage',
        'material_kind' => CurriculumMaterialKind::ApprovedPdf->value,
        'file' => UploadedFile::fake()->createWithContent($filename, $contents),
    ])->assertRedirect();

    $document = CurriculumDocument::query()->firstOrFail();

    expect($document->extracted_text)->toBeNull()
        ->and(Storage::disk('local')->get($document->file_path))->toBe($contents);
})->with([
    'pdf' => ['passage.pdf', '%PDF-1.4 fake pdf bytes'],
    'docx' => ['worksheet.docx', 'PK fake docx bytes'],
]);

it('accepts uploads even when the file is not a valid PDF', function (): void {
    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'title' => 'Corrupt upload',
        'material_kind' => CurriculumMaterialKind::Other->value,
        'file' => UploadedFile::fake()->createWithContent('broken.pdf', 'not really a pdf'),
    ])->assertRedirect();

    $document = CurriculumDocument::query()->firstOrFail();

    expect($document->extracted_text)->toBeNull()
        ->and(Storage::disk('local')->exists($document->file_path))->toBeTrue();
});

it('rejects unsupported file extensions', function (): void {
    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'title' => 'Spreadsheet',
        'material_kind' => CurriculumMaterialKind::Other->value,
        'file' => UploadedFile::fake()->createWithContent('data.csv', 'a,b,c'),
    ])->assertSessionHasErrors('file');

    expect(CurriculumDocument::query()->count())->toBe(0);
});

it('rejects files above the configured size limit', function (): void {
    config(['glc.curriculum.max_file_size_kb' => 10]);

    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'title' => 'Too large',
        'material_kind' => CurriculumMaterialKind::Other->value,
        'file' => UploadedFile::fake()->create('big.pdf', 11),
    ])->assertSessionHasErrors('file');
});

it('requires the full hierarchy tag set', function (): void {
    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        'title' => 'Untagged',
        'file' => UploadedFile::fake()->createWithContent('notes.txt', 'content'),
    ])->assertSessionHasErrors(['course_id', 'course_level_id', 'course_unit_id']);
});

it('rejects hierarchy tags that do not belong together', function (): void {
    $otherLevel = CourseLevel::factory()->create();

    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'course_level_id' => $otherLevel->id,
        'title' => 'Mismatched',
        'material_kind' => CurriculumMaterialKind::Notes->value,
        'file' => UploadedFile::fake()->createWithContent('notes.txt', 'content'),
    ])->assertSessionHasErrors(['course_level_id', 'course_unit_id']);
});

it('rejects a lesson from a different unit', function (): void {
    $foreignLesson = CourseLesson::factory()->create();

    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'course_lesson_id' => $foreignLesson->id,
        'title' => 'Wrong lesson',
        'material_kind' => CurriculumMaterialKind::Notes->value,
        'file' => UploadedFile::fake()->createWithContent('notes.txt', 'content'),
    ])->assertSessionHasErrors('course_lesson_id');
});

it('keeps draft uploads out of tutor retrieval', function (): void {
    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'title' => 'Draft only',
        'material_kind' => CurriculumMaterialKind::Summary->value,
        'file' => UploadedFile::fake()->createWithContent('draft.txt', 'draft content'),
    ])->assertRedirect();

    $document = CurriculumDocument::query()->firstOrFail();

    expect($document->isTutorRetrievable())->toBeFalse()
        ->and(CurriculumDocument::query()->published()->count())->toBe(0);
});
