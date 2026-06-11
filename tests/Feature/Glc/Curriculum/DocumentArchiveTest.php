<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Models\Glc\AuditLog;
use App\Models\Glc\CurriculumDocument;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    config(['gemini.api_key' => 'test-key']);

    $this->supervisor = User::factory()->academicSupervisor()->create();
});

it('archives a published document, removes it from the index, and audits', function (): void {
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/documents/doc-1' => Http::response([]),
    ]);

    $document = CurriculumDocument::factory()->published()->create([
        'gemini_document_name' => 'fileSearchStores/glc-store/documents/doc-1',
    ]);

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.archive', $document))
        ->assertRedirect();

    $document->refresh();

    expect($document->status)->toBe(CurriculumDocumentStatus::Archived)
        ->and($document->archived_at)->not->toBeNull()
        ->and($document->index_status)->toBe(CurriculumIndexStatus::Removed)
        ->and($document->gemini_document_name)->toBeNull()
        ->and($document->gemini_file_name)->toBeNull()
        ->and($document->isTutorRetrievable())->toBeFalse()
        ->and(CurriculumDocument::query()->published()->count())->toBe(0);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/documents/doc-1');

    $audit = AuditLog::query()->where('action', AuditAction::CurriculumArchived->value)->firstOrFail();

    expect($audit->actor_id)->toBe($this->supervisor->id)
        ->and($audit->subject_id)->toBe($document->id);
});

it('archives a published document that was never indexed without calling the API', function (): void {
    Http::fake();

    $document = CurriculumDocument::factory()->published()->create([
        'gemini_file_name' => null,
        'gemini_document_name' => null,
        'index_status' => CurriculumIndexStatus::Failed,
        'index_error' => 'previous failure',
    ]);

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.archive', $document))
        ->assertRedirect();

    expect($document->refresh()->index_status)->toBe(CurriculumIndexStatus::Removed);
    Http::assertNothingSent();
});

it('rejects archiving a draft document', function (): void {
    $document = CurriculumDocument::factory()->create();

    $this->actingAs($this->supervisor)
        ->postJson(route('curriculum.documents.archive', $document))
        ->assertStatus(422);

    expect($document->refresh()->status)->toBe(CurriculumDocumentStatus::Draft);
});

it('records a failed removal when the store deletion fails', function (): void {
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/documents/doc-2' => Http::response(['error' => 'unavailable'], 503),
    ]);

    $document = CurriculumDocument::factory()->published()->create([
        'gemini_document_name' => 'fileSearchStores/glc-store/documents/doc-2',
    ]);

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.archive', $document))
        ->assertRedirect();

    $document->refresh();

    expect($document->status)->toBe(CurriculumDocumentStatus::Archived)
        ->and($document->index_status)->toBe(CurriculumIndexStatus::Failed)
        ->and($document->index_error)->not->toBeNull();
});
