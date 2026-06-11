<?php

declare(strict_types=1);

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Enums\SettingKey;
use App\Models\Glc\CourseLesson;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Models\Setting;
use App\Services\Glc\Curriculum\CurriculumIndexService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    config(['gemini.api_key' => 'test-key']);

    $this->service = app(CurriculumIndexService::class);
});

function glcCurriculumIndexableDocument(array $attributes = []): CurriculumDocument
{
    $document = CurriculumDocument::factory()->create([
        'title' => 'Unit 1 Summary',
        'file_path' => 'glc/curriculum/1/unit-1-summary.txt',
        'original_filename' => 'unit-1-summary.txt',
        'format' => 'txt',
        'status' => CurriculumDocumentStatus::Published,
        'published_at' => now(),
        ...$attributes,
    ]);

    Storage::disk('local')->put($document->file_path, 'Unit one summary content.');

    return $document;
}

it('creates the store on first use, uploads, imports with contract metadata, and persists names', function (): void {
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores' => Http::response(['name' => 'fileSearchStores/glc-store']),
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-9', 'uri' => 'https://example.test/file-9']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['name' => 'fileSearchStores/glc-store/operations/op-9']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-9' => Http::response([
            'done' => true,
            'response' => ['documentName' => 'fileSearchStores/glc-store/documents/doc-9'],
        ]),
    ]);

    $document = glcCurriculumIndexableDocument();

    $this->service->index($document);

    $document->refresh();

    expect($document->index_status)->toBe(CurriculumIndexStatus::Indexed)
        ->and($document->gemini_file_name)->toBe('files/file-9')
        ->and($document->gemini_document_name)->toBe('fileSearchStores/glc-store/documents/doc-9')
        ->and(Setting::get(SettingKey::GlcCurriculumStoreName))->toBe('fileSearchStores/glc-store');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/fileSearchStores'
        && $request['displayName'] === config('glc.curriculum.store_display_name'));

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/upload/v1beta/files'
        && $request->hasHeader('X-Goog-Upload-Protocol', 'multipart')
        && $request->hasHeader('x-goog-api-key', 'test-key'));

    Http::assertSent(function (Request $request) use ($document): bool {
        if (! str_contains($request->url(), ':importFile')) {
            return false;
        }

        $payload = $request->data();
        $metadata = collect($payload['custom_metadata'] ?? []);

        return $payload['file_name'] === 'files/file-9'
            && $metadata->contains(fn (array $entry): bool => $entry === ['key' => 'course_id', 'numericValue' => $document->course_id])
            && $metadata->contains(fn (array $entry): bool => $entry === ['key' => 'course_level_id', 'numericValue' => $document->course_level_id])
            && $metadata->contains(fn (array $entry): bool => $entry === ['key' => 'course_unit_id', 'numericValue' => $document->course_unit_id])
            && $metadata->contains(fn (array $entry): bool => $entry === ['key' => 'status', 'stringValue' => 'published'])
            && $metadata->doesntContain(fn (array $entry): bool => $entry['key'] === 'course_lesson_id');
    });
});

it('includes course_lesson_id metadata when the document is tagged to a lesson', function (): void {
    $lesson = CourseLesson::factory()->create();
    $unit = CourseUnit::query()->findOrFail($lesson->course_unit_id);

    $document = glcCurriculumIndexableDocument([
        'course_unit_id' => $unit->id,
        'course_level_id' => $unit->course_level_id,
        'course_lesson_id' => $lesson->id,
    ]);

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-store');

    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-2', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['name' => 'fileSearchStores/glc-store/operations/op-2']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-2' => Http::response([
            'done' => true,
            'response' => ['documentName' => 'fileSearchStores/glc-store/documents/doc-2'],
        ]),
    ]);

    $this->service->index($document);

    Http::assertSent(function (Request $request) use ($lesson): bool {
        if (! str_contains($request->url(), ':importFile')) {
            return false;
        }

        return collect($request->data()['custom_metadata'] ?? [])
            ->contains(fn (array $entry): bool => $entry === ['key' => 'course_lesson_id', 'numericValue' => $lesson->id]);
    });
});

it('reuses an existing store from settings without creating a new one', function (): void {
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/existing-store');

    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-3', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/existing-store:importFile' => Http::response(['name' => 'fileSearchStores/existing-store/operations/op-3']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/existing-store/operations/op-3' => Http::response([
            'done' => true,
            'response' => ['documentName' => 'fileSearchStores/existing-store/documents/doc-3'],
        ]),
    ]);

    $document = glcCurriculumIndexableDocument();

    $this->service->index($document);

    expect($document->refresh()->index_status)->toBe(CurriculumIndexStatus::Indexed);

    Http::assertNotSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/fileSearchStores'
        && $request->method() === 'POST');
});

it('polls the import operation until it is done', function (): void {
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-store');

    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-4', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['name' => 'fileSearchStores/glc-store/operations/op-4']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-4' => Http::sequence()
            ->push(['done' => false])
            ->push(['done' => false])
            ->push(['done' => true, 'response' => ['documentName' => 'fileSearchStores/glc-store/documents/doc-4']]),
    ]);

    $document = glcCurriculumIndexableDocument();

    $this->service->index($document);

    expect($document->refresh()->gemini_document_name)->toBe('fileSearchStores/glc-store/documents/doc-4');

    Http::assertSentCount(5);
});

it('falls back to the documents list when the operation response has no document name', function (): void {
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-store');

    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-5', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['name' => 'fileSearchStores/glc-store/operations/op-5']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-5' => Http::response(['done' => true]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/documents' => Http::response([
            'documents' => [
                ['name' => 'fileSearchStores/glc-store/documents/other', 'displayName' => 'Other Title'],
                ['name' => 'fileSearchStores/glc-store/documents/doc-5', 'displayName' => 'Unit 1 Summary'],
            ],
        ]),
    ]);

    $document = glcCurriculumIndexableDocument();

    $this->service->index($document);

    expect($document->refresh()->gemini_document_name)->toBe('fileSearchStores/glc-store/documents/doc-5');
});

it('marks the document failed with the error message when the import fails', function (): void {
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-store');

    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-6', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['error' => 'quota exceeded'], 429),
    ]);

    $document = glcCurriculumIndexableDocument();

    $this->service->index($document);

    $document->refresh();

    expect($document->index_status)->toBe(CurriculumIndexStatus::Failed)
        ->and($document->index_error)->toContain('quota exceeded')
        ->and($document->status)->toBe(CurriculumDocumentStatus::Published);
});

it('marks the document failed when the operation reports an error', function (): void {
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-store');

    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-7', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['name' => 'fileSearchStores/glc-store/operations/op-7']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-7' => Http::response([
            'done' => true,
            'error' => ['code' => 13, 'message' => 'internal import failure'],
        ]),
    ]);

    $document = glcCurriculumIndexableDocument();

    $this->service->index($document);

    expect($document->refresh()->index_status)->toBe(CurriculumIndexStatus::Failed)
        ->and($document->index_error)->toContain('internal import failure');
});
