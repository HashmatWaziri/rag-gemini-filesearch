<?php

declare(strict_types=1);

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Models\Glc\Course;
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

    $this->tags = [
        'course_id' => $this->course->id,
        'course_level_id' => $this->level->id,
        'course_unit_id' => $this->unit->id,
    ];
});

it('uploads multiple files sharing one tag set and reports per-file results', function (): void {
    $response = $this->actingAs($this->supervisor)->post(route('curriculum.documents.bulk'), [
        ...$this->tags,
        'files' => [
            UploadedFile::fake()->createWithContent('unit-1-summary.txt', 'Summary one'),
            UploadedFile::fake()->createWithContent('unit-1-worksheet.txt', 'Worksheet one'),
            UploadedFile::fake()->createWithContent('grades.csv', 'a,b,c'),
        ],
    ]);

    $response->assertRedirect(route('curriculum.index'));
    $response->assertSessionHas('bulk_report');

    $report = collect(session('bulk_report'));

    expect($report)->toHaveCount(3);

    $summaryRow = $report->firstWhere('filename', 'unit-1-summary.txt');
    $worksheetRow = $report->firstWhere('filename', 'unit-1-worksheet.txt');
    $csvRow = $report->firstWhere('filename', 'grades.csv');

    expect($summaryRow['success'])->toBeTrue()
        ->and($worksheetRow['success'])->toBeTrue()
        ->and($csvRow['success'])->toBeFalse()
        ->and($csvRow['error'])->toContain('".csv" files aren\'t supported. Please upload PDF, Word (.docx), or plain text (.txt) files.');

    expect(CurriculumDocument::query()->count())->toBe(2);

    $summary = CurriculumDocument::query()->where('title', 'unit-1-summary')->firstOrFail();

    expect($summary)
        ->course_id->toBe($this->course->id)
        ->course_level_id->toBe($this->level->id)
        ->course_unit_id->toBe($this->unit->id)
        ->status->toBe(CurriculumDocumentStatus::Draft)
        ->extracted_text->toBeNull();
});

it('reports oversize files per-file without failing the batch', function (): void {
    config(['glc.curriculum.max_file_size_kb' => 10]);

    $this->actingAs($this->supervisor)->post(route('curriculum.documents.bulk'), [
        ...$this->tags,
        'files' => [
            UploadedFile::fake()->createWithContent('fine.txt', 'small enough'),
            UploadedFile::fake()->create('huge.txt', 11),
        ],
    ])->assertRedirect(route('curriculum.index'));

    $report = collect(session('bulk_report'));

    expect($report->firstWhere('filename', 'fine.txt')['success'])->toBeTrue()
        ->and($report->firstWhere('filename', 'huge.txt')['success'])->toBeFalse()
        ->and($report->firstWhere('filename', 'huge.txt')['error'])->toContain('size limit')
        ->and(CurriculumDocument::query()->count())->toBe(1);
});

it('rejects the whole request when too many files are sent', function (): void {
    config(['glc.curriculum.max_bulk_files' => 2]);

    $this->actingAs($this->supervisor)->post(route('curriculum.documents.bulk'), [
        ...$this->tags,
        'files' => [
            UploadedFile::fake()->createWithContent('one.txt', 'one'),
            UploadedFile::fake()->createWithContent('two.txt', 'two'),
            UploadedFile::fake()->createWithContent('three.txt', 'three'),
        ],
    ])->assertSessionHasErrors('files');

    expect(CurriculumDocument::query()->count())->toBe(0);
});

it('rejects bulk uploads with invalid hierarchy tags', function (): void {
    $this->actingAs($this->supervisor)->post(route('curriculum.documents.bulk'), [
        'course_id' => $this->course->id,
        'course_level_id' => $this->level->id,
        'course_unit_id' => 999,
        'files' => [UploadedFile::fake()->createWithContent('one.txt', 'one')],
    ])->assertSessionHasErrors('course_unit_id');
});
