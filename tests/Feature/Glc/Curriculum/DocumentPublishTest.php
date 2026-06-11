<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Jobs\Glc\Curriculum\IndexCurriculumDocumentJob;
use App\Models\Glc\AuditLog;
use App\Models\Glc\CurriculumDocument;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');

    $this->supervisor = User::factory()->academicSupervisor()->create();
});

function glcCurriculumDraftDocument(): CurriculumDocument
{
    $document = CurriculumDocument::factory()->create([
        'file_path' => 'glc/curriculum/1/draft.txt',
        'original_filename' => 'draft.txt',
        'format' => 'txt',
        'extracted_text' => 'Draft body text.',
    ]);

    Storage::disk('local')->put($document->file_path, 'Draft body text.');

    return $document;
}

it('rejects publishing without the preview confirmation flag', function (): void {
    Queue::fake();
    $document = glcCurriculumDraftDocument();

    $this->actingAs($this->supervisor)
        ->postJson(route('curriculum.documents.publish', $document), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('preview_confirmed');

    expect($document->refresh()->status)->toBe(CurriculumDocumentStatus::Draft);
    Queue::assertNothingPushed();
});

it('publishes a draft after preview confirmation, audits, and queues indexing', function (): void {
    Queue::fake();
    $document = glcCurriculumDraftDocument();

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.publish', $document), ['preview_confirmed' => true])
        ->assertRedirect();

    $document->refresh();

    expect($document->status)->toBe(CurriculumDocumentStatus::Published)
        ->and($document->published_at)->not->toBeNull()
        ->and($document->isTutorRetrievable())->toBeTrue()
        ->and(CurriculumDocument::query()->published()->count())->toBe(1);

    Queue::assertPushed(IndexCurriculumDocumentJob::class, fn (IndexCurriculumDocumentJob $job): bool => $job->document->is($document));

    $audit = AuditLog::query()->where('action', AuditAction::CurriculumPublished->value)->firstOrFail();

    expect($audit->actor_id)->toBe($this->supervisor->id)
        ->and($audit->subject_id)->toBe($document->id)
        ->and($audit->subject_type)->toBe(CurriculumDocument::class);
});

it('rejects publishing a document that is not a draft', function (): void {
    Queue::fake();
    $document = CurriculumDocument::factory()->published()->create();

    $this->actingAs($this->supervisor)
        ->postJson(route('curriculum.documents.publish', $document), ['preview_confirmed' => true])
        ->assertStatus(422);

    Queue::assertNothingPushed();
});

it('runs the full publish pipeline against the Gemini API', function (): void {
    config(['gemini.api_key' => 'test-key']);

    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores' => Http::response(['name' => 'fileSearchStores/glc-store']),
        'https://generativelanguage.googleapis.com/upload/v1beta/files' => Http::response(['file' => ['name' => 'files/file-1', 'uri' => 'https://example.test/file-1']]),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store:importFile' => Http::response(['name' => 'fileSearchStores/glc-store/operations/op-1']),
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/operations/op-1' => Http::response([
            'done' => true,
            'response' => ['documentName' => 'fileSearchStores/glc-store/documents/doc-1'],
        ]),
    ]);

    $document = glcCurriculumDraftDocument();

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.publish', $document), ['preview_confirmed' => true])
        ->assertRedirect();

    $document->refresh();

    expect($document->status)->toBe(CurriculumDocumentStatus::Published)
        ->and($document->index_status)->toBe(CurriculumIndexStatus::Indexed)
        ->and($document->gemini_file_name)->toBe('files/file-1')
        ->and($document->gemini_document_name)->toBe('fileSearchStores/glc-store/documents/doc-1')
        ->and($document->index_error)->toBeNull();
});

it('records a failed index without losing the published state when the API key is missing', function (): void {
    config(['gemini.api_key' => null]);
    $document = glcCurriculumDraftDocument();

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.publish', $document), ['preview_confirmed' => true])
        ->assertRedirect();

    $document->refresh();

    expect($document->status)->toBe(CurriculumDocumentStatus::Published)
        ->and($document->index_status)->toBe(CurriculumIndexStatus::Failed)
        ->and($document->index_error)->toContain('API key');
});

it('skips indexing when the document is no longer published at job runtime', function (): void {
    config(['gemini.api_key' => 'test-key']);
    Http::fake();

    $document = glcCurriculumDraftDocument();

    (new IndexCurriculumDocumentJob($document))->handle(app(App\Services\Glc\Curriculum\CurriculumIndexService::class));

    expect($document->refresh()->index_status)->toBe(CurriculumIndexStatus::Pending);
    Http::assertNothingSent();
});
