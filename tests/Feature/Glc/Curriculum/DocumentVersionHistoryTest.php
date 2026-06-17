<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\CurriculumDocumentStatus;
use App\Models\Glc\AuditLog;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\CurriculumDocumentVersion;
use App\Models\User;
use App\Services\Glc\Curriculum\CurriculumUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    $this->withoutVite();

    $this->supervisor = User::factory()->academicSupervisor()->create();
    $this->uploads = app(CurriculumUploadService::class);
});

it('snapshots the current document when replacing', function (): void {
    $document = CurriculumDocument::factory()->published()->create([
        'file_path' => 'glc/curriculum/1/original.txt',
        'original_filename' => 'original.txt',
        'format' => 'txt',
        'extracted_text' => 'Original content.',
        'version' => 1,
    ]);

    Storage::disk('local')->put($document->file_path, 'Original content.');

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.replace', $document), [
            'file' => UploadedFile::fake()->createWithContent('updated-version.txt', 'Updated worksheet content v2.'),
        ])
        ->assertRedirect();

    $document->refresh();

    expect($document->version)->toBe(2)
        ->and(CurriculumDocumentVersion::query()->count())->toBe(1);

    $snapshot = CurriculumDocumentVersion::query()->firstOrFail();

    expect($snapshot->curriculum_document_id)->toBe($document->id)
        ->and($snapshot->version)->toBe(1)
        ->and($snapshot->original_filename)->toBe('original.txt')
        ->and($snapshot->status)->toBe(CurriculumDocumentStatus::Published)
        ->and($snapshot->extracted_text)->toBe('Original content.');

    $versionPath = $this->uploads->versionedFilePath(
        $document->course_id,
        $document->id,
        1,
        'txt',
    );

    Storage::disk('local')->assertExists($versionPath);
    Storage::disk('local')->assertMissing('glc/curriculum/1/original.txt');
    expect(Storage::disk('local')->get($versionPath))->toBe('Original content.');
});

it('does not create a version row on initial upload', function (): void {
    $unit = App\Models\Glc\CourseUnit::factory()->create();
    $level = App\Models\Glc\CourseLevel::query()->findOrFail($unit->course_level_id);

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.store'), [
            'title' => 'First upload',
            'material_kind' => 'worksheet',
            'course_id' => $level->course_id,
            'course_level_id' => $level->id,
            'course_unit_id' => $unit->id,
            'file' => UploadedFile::fake()->createWithContent('first.txt', 'First version.'),
        ])
        ->assertRedirect();

    expect(CurriculumDocumentVersion::query()->count())->toBe(0);
});

it('restores a historical version as a new draft', function (): void {
    $document = CurriculumDocument::factory()->create([
        'file_path' => 'glc/curriculum/1/current.txt',
        'original_filename' => 'current.txt',
        'format' => 'txt',
        'version' => 2,
    ]);

    Storage::disk('local')->put($document->file_path, 'Current content.');

    $versionPath = $this->uploads->versionedFilePath(
        $document->course_id,
        $document->id,
        1,
        'txt',
    );

    Storage::disk('local')->put($versionPath, 'Historical content.');

    CurriculumDocumentVersion::factory()->create([
        'curriculum_document_id' => $document->id,
        'version' => 1,
        'original_filename' => 'historical.txt',
        'file_path' => $versionPath,
        'format' => 'txt',
        'status' => CurriculumDocumentStatus::Published,
        'published_at' => now()->subDay(),
    ]);

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.versions.restore', [$document, 1]))
        ->assertRedirect();

    $document->refresh();

    expect($document->version)->toBe(3)
        ->and($document->status)->toBe(CurriculumDocumentStatus::Draft)
        ->and($document->original_filename)->toBe('historical.txt')
        ->and(Storage::disk('local')->get($document->file_path))->toBe('Historical content.')
        ->and(CurriculumDocumentVersion::query()->where('version', 2)->exists())->toBeTrue();

    $audit = AuditLog::query()->where('action', AuditAction::CurriculumReplaced->value)->latest('id')->firstOrFail();

    expect($audit->details['restored_from_version'])->toBe(1)
        ->and($audit->details['previous_version'])->toBe(2)
        ->and($audit->details['version'])->toBe(3);
});

it('lists versions on the document detail page', function (): void {
    $document = CurriculumDocument::factory()->create(['version' => 2]);

    $versionPath = $this->uploads->versionedFilePath(
        $document->course_id,
        $document->id,
        1,
        'pdf',
    );

    Storage::disk('local')->put($versionPath, 'archived bytes');

    CurriculumDocumentVersion::factory()->create([
        'curriculum_document_id' => $document->id,
        'version' => 1,
        'original_filename' => 'archived.pdf',
        'file_path' => $versionPath,
        'format' => 'pdf',
        'published_at' => now()->subWeek(),
    ]);

    $this->actingAs($this->supervisor)
        ->get(route('curriculum.documents.show', $document))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/curriculum/show')
            ->has('versions', 1)
            ->where('versions.0.version', 1)
            ->where('versions.0.original_filename', 'archived.pdf')
            ->where('versions.0.can_restore', true));
});
