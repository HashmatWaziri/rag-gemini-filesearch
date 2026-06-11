<?php

declare(strict_types=1);

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLesson;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

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

it('uploads a TXT file as a draft with extracted text', function (): void {
    Queue::fake();

    $response = $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'course_lesson_id' => $this->lesson->id,
        'title' => 'Unit notes',
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
        ->extracted_text->toBe('GLC summary: present simple routines.')
        ->status->toBe(CurriculumDocumentStatus::Draft)
        ->index_status->toBe(CurriculumIndexStatus::Pending)
        ->version->toBe(1)
        ->uploaded_by->toBe($this->supervisor->id);

    expect(str_starts_with($document->file_path, 'glc/curriculum/'.$this->course->id.'/'))->toBeTrue();
    Storage::disk('local')->assertExists($document->file_path);

    Queue::assertNothingPushed();
});

it('uploads a PDF file and extracts its text', function (): void {
    $pdfContent = Pdf::loadHTML('<h1>Reading passage</h1><p>The market opens early every Saturday.</p>')->output();

    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'title' => 'Reading passage',
        'file' => UploadedFile::fake()->createWithContent('passage.pdf', $pdfContent),
    ])->assertRedirect();

    $document = CurriculumDocument::query()->firstOrFail();

    expect($document->format)->toBe('pdf')
        ->and($document->extracted_text)->toContain('The market opens early every Saturday');
});

it('uploads a DOCX file and extracts its text', function (): void {
    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $section->addText('Grammar worksheet: complete each sentence with the correct form.');

    $temporaryPath = tempnam(sys_get_temp_dir(), 'glc-docx-');
    IOFactory::createWriter($phpWord, 'Word2007')->save($temporaryPath);
    $docxContent = (string) file_get_contents($temporaryPath);
    unlink($temporaryPath);

    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'title' => 'Grammar worksheet',
        'file' => UploadedFile::fake()->createWithContent('worksheet.docx', $docxContent),
    ])->assertRedirect();

    $document = CurriculumDocument::query()->firstOrFail();

    expect($document->format)->toBe('docx')
        ->and($document->extracted_text)->toContain('Grammar worksheet: complete each sentence with the correct form.');
});

it('rejects unsupported file extensions', function (): void {
    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'title' => 'Spreadsheet',
        'file' => UploadedFile::fake()->createWithContent('data.csv', 'a,b,c'),
    ])->assertSessionHasErrors('file');

    expect(CurriculumDocument::query()->count())->toBe(0);
});

it('rejects files above the configured size limit', function (): void {
    config(['glc.curriculum.max_file_size_kb' => 10]);

    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'title' => 'Too large',
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
        'file' => UploadedFile::fake()->createWithContent('notes.txt', 'content'),
    ])->assertSessionHasErrors(['course_level_id', 'course_unit_id']);
});

it('rejects a lesson from a different unit', function (): void {
    $foreignLesson = CourseLesson::factory()->create();

    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'course_lesson_id' => $foreignLesson->id,
        'title' => 'Wrong lesson',
        'file' => UploadedFile::fake()->createWithContent('notes.txt', 'content'),
    ])->assertSessionHasErrors('course_lesson_id');
});

it('reports extraction failures and cleans up the stored file', function (): void {
    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'title' => 'Corrupt upload',
        'file' => UploadedFile::fake()->createWithContent('broken.pdf', 'not really a pdf'),
    ])->assertSessionHasErrors('file');

    expect(CurriculumDocument::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('keeps draft uploads out of tutor retrieval', function (): void {
    $this->actingAs($this->supervisor)->post(route('curriculum.documents.store'), [
        ...$this->tags,
        'title' => 'Draft only',
        'file' => UploadedFile::fake()->createWithContent('draft.txt', 'draft content'),
    ])->assertRedirect();

    $document = CurriculumDocument::query()->firstOrFail();

    expect($document->isTutorRetrievable())->toBeFalse()
        ->and(CurriculumDocument::query()->published()->count())->toBe(0);
});
