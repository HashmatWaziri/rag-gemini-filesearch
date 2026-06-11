<?php

declare(strict_types=1);

use App\Enums\Glc\CurriculumIndexStatus;
use App\Enums\SettingKey;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\PlacementItem;
use App\Models\Setting;
use App\Services\Glc\Curriculum\GeminiFileSearchService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
});

function glcRebuildPublishedDocument(string $filename, ?string $storeDocumentName = null): CurriculumDocument
{
    $document = CurriculumDocument::factory()->published()->create([
        'title' => pathinfo($filename, PATHINFO_FILENAME),
        'original_filename' => $filename,
        'file_path' => 'glc/curriculum/1/'.$filename,
        'format' => 'txt',
        'gemini_file_name' => null,
        'gemini_document_name' => $storeDocumentName,
    ]);

    Storage::disk('local')->put($document->file_path, 'Content of '.$filename);

    return $document;
}

it('re-imports every published document into a fresh store and reports counts', function (): void {
    config(['gemini.api_key' => 'test-key']);

    $first = glcRebuildPublishedDocument('unit-1-summary.txt');
    $second = glcRebuildPublishedDocument('unit-2-worksheet.txt');

    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores' => Http::response(['name' => 'fileSearchStores/glc-store']),
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::sequence()
            ->push(['file' => ['name' => 'files/file-1', 'uri' => 'u']])
            ->push(['file' => ['name' => 'files/file-2', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::sequence()
            ->push(['name' => 'fileSearchStores/glc-store/operations/op-1'])
            ->push(['name' => 'fileSearchStores/glc-store/operations/op-2']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-1' => Http::response([
            'done' => true,
            'response' => ['documentName' => 'fileSearchStores/glc-store/documents/doc-1'],
        ]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-2' => Http::response([
            'done' => true,
            'response' => ['documentName' => 'fileSearchStores/glc-store/documents/doc-2'],
        ]),
    ]);

    $result = app(GeminiFileSearchService::class)->rebuildStore();

    expect($result)->toBe(['total' => 2, 'succeeded' => 2, 'failed' => 0])
        ->and($first->refresh()->index_status)->toBe(CurriculumIndexStatus::Indexed)
        ->and($first->gemini_document_name)->toBe('fileSearchStores/glc-store/documents/doc-1')
        ->and($second->refresh()->index_status)->toBe(CurriculumIndexStatus::Indexed)
        ->and($second->gemini_document_name)->toBe('fileSearchStores/glc-store/documents/doc-2')
        ->and(Setting::get(SettingKey::GlcCurriculumStoreName))->toBe('fileSearchStores/glc-store');
});

it('tolerates a per-document failure, counts it, and continues with the rest', function (): void {
    config(['gemini.api_key' => 'test-key']);
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-store');

    $failing = glcRebuildPublishedDocument('broken.txt');
    $healthy = glcRebuildPublishedDocument('healthy.txt');

    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::sequence()
            ->push(['error' => 'upload exploded'], 500)
            ->push(['file' => ['name' => 'files/file-ok', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['name' => 'fileSearchStores/glc-store/operations/op-ok']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-ok' => Http::response([
            'done' => true,
            'response' => ['documentName' => 'fileSearchStores/glc-store/documents/doc-ok'],
        ]),
    ]);

    $result = app(GeminiFileSearchService::class)->rebuildStore();

    expect($result)->toBe(['total' => 2, 'succeeded' => 1, 'failed' => 1])
        ->and($failing->refresh()->index_status)->toBe(CurriculumIndexStatus::Failed)
        ->and($failing->index_error)->toContain('File upload failed')
        ->and($healthy->refresh()->index_status)->toBe(CurriculumIndexStatus::Indexed)
        ->and($healthy->gemini_document_name)->toBe('fileSearchStores/glc-store/documents/doc-ok');
});

it('replaces a document\'s prior store entry instead of duplicating it', function (): void {
    config(['gemini.api_key' => 'test-key']);
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-store');

    $document = glcRebuildPublishedDocument('unit-1-summary.txt', 'fileSearchStores/glc-store/documents/old-entry');

    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-new', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['name' => 'fileSearchStores/glc-store/operations/op-new']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-new' => Http::response([
            'done' => true,
            'response' => ['documentName' => 'fileSearchStores/glc-store/documents/doc-new'],
        ]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/documents/old-entry' => Http::response([]),
    ]);

    $result = app(GeminiFileSearchService::class)->rebuildStore();

    expect($result)->toBe(['total' => 1, 'succeeded' => 1, 'failed' => 0])
        ->and($document->refresh()->gemini_document_name)->toBe('fileSearchStores/glc-store/documents/doc-new');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/documents/old-entry');
});

it('returns cleanly without HTTP calls when Gemini is not configured', function (): void {
    config(['gemini.api_key' => null]);
    Http::fake();

    $document = glcRebuildPublishedDocument('unit-1-summary.txt');

    $result = app(GeminiFileSearchService::class)->rebuildStore();

    expect($result)->toBe(['total' => 0, 'succeeded' => 0, 'failed' => 0])
        ->and($document->refresh()->index_status)->toBe(CurriculumIndexStatus::Indexed);

    Http::assertNothingSent();
});

it('never re-imports archived or draft documents or placement content', function (): void {
    config(['gemini.api_key' => 'test-key']);
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-store');

    glcRebuildPublishedDocument('published.txt');
    CurriculumDocument::factory()->create(['title' => 'Still a draft']);
    CurriculumDocument::factory()->archived()->create(['title' => 'Long archived']);
    PlacementItem::factory()->audioClip()->create();

    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-pub', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['name' => 'fileSearchStores/glc-store/operations/op-pub']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-pub' => Http::response([
            'done' => true,
            'response' => ['documentName' => 'fileSearchStores/glc-store/documents/doc-pub'],
        ]),
    ]);

    $result = app(GeminiFileSearchService::class)->rebuildStore();

    expect($result)->toBe(['total' => 1, 'succeeded' => 1, 'failed' => 0]);

    Http::assertSentCount(3);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/upload/v1beta/files'
        && str_contains($request->body(), 'published.txt'));
});

it('prints a summary and exits non-zero when documents fail to re-import', function (): void {
    config(['gemini.api_key' => 'test-key']);
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-store');

    glcRebuildPublishedDocument('broken.txt');

    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['error' => 'upload exploded'], 500),
    ]);

    $this->artisan('rebuild:gemini-file-search-store')
        ->expectsOutputToContain('Published documents found')
        ->expectsOutputToContain('1 document(s) could not be re-imported')
        ->assertExitCode(1);
});

it('prints a summary and exits zero when the rebuild succeeds', function (): void {
    config(['gemini.api_key' => 'test-key']);
    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-store');

    glcRebuildPublishedDocument('unit-1-summary.txt');

    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-1', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['name' => 'fileSearchStores/glc-store/operations/op-1']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-1' => Http::response([
            'done' => true,
            'response' => ['documentName' => 'fileSearchStores/glc-store/documents/doc-1'],
        ]),
    ]);

    $this->artisan('rebuild:gemini-file-search-store')
        ->expectsOutputToContain('Published documents found')
        ->expectsOutputToContain('File Search store rebuild completed.')
        ->assertExitCode(0);
});

it('warns and exits zero without HTTP calls when run unconfigured', function (): void {
    config(['gemini.api_key' => null]);
    Http::fake();

    glcRebuildPublishedDocument('unit-1-summary.txt');

    $this->artisan('rebuild:gemini-file-search-store')
        ->expectsOutputToContain('Gemini API key is not configured. Nothing was rebuilt.')
        ->assertExitCode(0);

    Http::assertNothingSent();
});
