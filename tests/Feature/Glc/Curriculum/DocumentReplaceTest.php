<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Enums\SettingKey;
use App\Models\Glc\AuditLog;
use App\Models\Glc\CurriculumDocument;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    config(['gemini.api_key' => 'test-key']);

    $this->supervisor = User::factory()->academicSupervisor()->create();
});

it('replaces a published document: version bump, back to draft, previous store entry kept live', function (): void {
    Http::fake();

    $document = CurriculumDocument::factory()->published()->create([
        'file_path' => 'glc/curriculum/1/original.txt',
        'original_filename' => 'original.txt',
        'format' => 'txt',
        'extracted_text' => 'Original content.',
        'gemini_document_name' => 'fileSearchStores/glc-store/documents/old-doc',
    ]);

    Storage::disk('local')->put($document->file_path, 'Original content.');

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.replace', $document), [
            'file' => UploadedFile::fake()->createWithContent('updated-version.txt', 'Updated worksheet content v2.'),
        ])
        ->assertRedirect();

    $document->refresh();

    expect($document->version)->toBe(2)
        ->and($document->status)->toBe(CurriculumDocumentStatus::Draft)
        ->and($document->published_at)->toBeNull()
        ->and($document->original_filename)->toBe('updated-version.txt')
        ->and($document->extracted_text)->toBe('Updated worksheet content v2.')
        ->and($document->gemini_document_name)->toBe('fileSearchStores/glc-store/documents/old-doc')
        ->and($document->index_status)->toBe(CurriculumIndexStatus::Pending)
        ->and($document->index_error)->toBeNull()
        ->and($document->isTutorRetrievable())->toBeFalse()
        ->and(CurriculumDocument::query()->published()->count())->toBe(0);

    Storage::disk('local')->assertExists($document->file_path);
    Storage::disk('local')->assertMissing('glc/curriculum/1/original.txt');

    Http::assertNothingSent();

    $audit = AuditLog::query()->where('action', AuditAction::CurriculumReplaced->value)->firstOrFail();

    expect($audit->actor_id)->toBe($this->supervisor->id)
        ->and($audit->details['previous_version'])->toBe(1)
        ->and($audit->details['version'])->toBe(2);
});

it('deletes the previous store entry only after the replacement import succeeds', function (): void {
    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-new', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['name' => 'fileSearchStores/glc-store/operations/op-new']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-new' => Http::response([
            'done' => true,
            'response' => ['documentName' => 'fileSearchStores/glc-store/documents/new-doc'],
        ]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/documents/old-doc' => Http::response([]),
    ]);

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-store');

    $document = CurriculumDocument::factory()->published()->create([
        'file_path' => 'glc/curriculum/1/original.txt',
        'original_filename' => 'original.txt',
        'format' => 'txt',
        'extracted_text' => 'Original content.',
        'gemini_document_name' => 'fileSearchStores/glc-store/documents/old-doc',
    ]);

    Storage::disk('local')->put($document->file_path, 'Original content.');

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.replace', $document), [
            'file' => UploadedFile::fake()->createWithContent('updated.txt', 'Updated content.'),
        ])
        ->assertRedirect();

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.publish', $document), ['preview_confirmed' => true])
        ->assertRedirect();

    $document->refresh();

    expect($document->status)->toBe(CurriculumDocumentStatus::Published)
        ->and($document->index_status)->toBe(CurriculumIndexStatus::Indexed)
        ->and($document->gemini_document_name)->toBe('fileSearchStores/glc-store/documents/new-doc');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/documents/old-doc');
});

it('keeps the previous store entry when the replacement import fails', function (): void {
    Http::fake([
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-new', 'uri' => 'u']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['error' => 'quota exceeded'], 429),
    ]);

    Setting::set(SettingKey::GlcCurriculumStoreName, 'fileSearchStores/glc-store');

    $document = CurriculumDocument::factory()->published()->create([
        'file_path' => 'glc/curriculum/1/original.txt',
        'original_filename' => 'original.txt',
        'format' => 'txt',
        'extracted_text' => 'Original content.',
        'gemini_document_name' => 'fileSearchStores/glc-store/documents/old-doc',
    ]);

    Storage::disk('local')->put($document->file_path, 'Original content.');

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.replace', $document), [
            'file' => UploadedFile::fake()->createWithContent('updated.txt', 'Updated content.'),
        ])
        ->assertRedirect();

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.publish', $document), ['preview_confirmed' => true])
        ->assertRedirect();

    $document->refresh();

    expect($document->index_status)->toBe(CurriculumIndexStatus::Failed)
        ->and($document->gemini_document_name)->toBe('fileSearchStores/glc-store/documents/old-doc');

    Http::assertNotSent(fn (Request $request): bool => $request->method() === 'DELETE');
});

it('replaces a draft document without touching the API', function (): void {
    $document = CurriculumDocument::factory()->create([
        'file_path' => 'glc/curriculum/1/draft.txt',
        'format' => 'txt',
        'extracted_text' => 'Draft v1.',
    ]);

    Storage::disk('local')->put($document->file_path, 'Draft v1.');

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.replace', $document), [
            'file' => UploadedFile::fake()->createWithContent('draft-v2.txt', 'Draft v2.'),
        ])
        ->assertRedirect();

    $document->refresh();

    expect($document->version)->toBe(2)
        ->and($document->extracted_text)->toBe('Draft v2.')
        ->and($document->status)->toBe(CurriculumDocumentStatus::Draft);
});

it('changes format when replacing with a different file type', function (): void {
    $document = CurriculumDocument::factory()->create([
        'file_path' => 'glc/curriculum/1/notes.pdf',
        'format' => 'pdf',
    ]);

    Storage::disk('local')->put($document->file_path, 'pdf-bytes');

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.replace', $document), [
            'file' => UploadedFile::fake()->createWithContent('notes.txt', 'Plain text now.'),
        ])
        ->assertRedirect();

    expect($document->refresh()->format)->toBe('txt');
});

it('rejects replacement files with unsupported extensions', function (): void {
    $document = CurriculumDocument::factory()->create(['version' => 1]);

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.replace', $document), [
            'file' => UploadedFile::fake()->createWithContent('data.csv', 'a,b'),
        ])
        ->assertSessionHasErrors('file');

    expect($document->refresh()->version)->toBe(1);
});

it('keeps the current version when replacement extraction fails', function (): void {
    $document = CurriculumDocument::factory()->create([
        'file_path' => 'glc/curriculum/1/good.txt',
        'format' => 'txt',
        'extracted_text' => 'Good content.',
        'version' => 1,
    ]);

    Storage::disk('local')->put($document->file_path, 'Good content.');

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.replace', $document), [
            'file' => UploadedFile::fake()->createWithContent('broken.pdf', 'not a real pdf'),
        ])
        ->assertSessionHasErrors('file');

    $document->refresh();

    expect($document->version)->toBe(1)
        ->and($document->extracted_text)->toBe('Good content.');

    Storage::disk('local')->assertExists('glc/curriculum/1/good.txt');
});
