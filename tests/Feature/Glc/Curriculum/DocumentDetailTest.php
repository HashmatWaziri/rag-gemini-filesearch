<?php

declare(strict_types=1);

use App\Enums\Glc\CurriculumIndexStatus;
use App\Jobs\Glc\Curriculum\IndexCurriculumDocumentJob;
use App\Jobs\Glc\Curriculum\RemoveCurriculumDocumentFromIndexJob;
use App\Models\Glc\CurriculumDocument;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    $this->withoutVite();

    $this->supervisor = User::factory()->academicSupervisor()->create();
});

it('shows the document detail page with stored file metadata', function (): void {
    $document = CurriculumDocument::factory()->create([
        'file_path' => 'glc/curriculum/1/draft.txt',
        'format' => 'txt',
        'original_filename' => 'draft.txt',
    ]);

    Storage::disk('local')->put($document->file_path, 'Draft body text.');

    $this->actingAs($this->supervisor)
        ->get(route('curriculum.documents.show', $document))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/curriculum/show')
            ->where('document.id', $document->id)
            ->where('document.has_stored_file', true)
            ->where('document.file_size_label', '16 B')
            ->where('document.status', 'draft')
            ->where('document.version', 1)
            ->where('canDelete', false)
            ->has('versions', 0));
});

it('exposes the delete capability only to admins', function (): void {
    $admin = User::factory()->admin()->create();
    $document = CurriculumDocument::factory()->create();

    $this->actingAs($admin)
        ->get(route('curriculum.documents.show', $document))
        ->assertInertia(fn ($page) => $page->where('canDelete', true));
});

it('shows the index error on the detail page after a failed index run', function (): void {
    $document = CurriculumDocument::factory()->published()->create([
        'index_status' => CurriculumIndexStatus::Failed,
        'index_error' => 'quota exceeded',
        'gemini_file_name' => null,
        'gemini_document_name' => null,
    ]);

    $this->actingAs($this->supervisor)
        ->get(route('curriculum.documents.show', $document))
        ->assertInertia(fn ($page) => $page
            ->where('document.index_status', 'failed')
            ->where('document.index_error', 'quota exceeded'));
});

it('queues a retry for a failed published document', function (): void {
    Queue::fake();

    $document = CurriculumDocument::factory()->published()->create([
        'index_status' => CurriculumIndexStatus::Failed,
        'index_error' => 'quota exceeded',
        'gemini_file_name' => null,
        'gemini_document_name' => null,
    ]);

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.reindex', $document))
        ->assertRedirect();

    $document->refresh();

    expect($document->index_status)->toBe(CurriculumIndexStatus::Pending)
        ->and($document->index_error)->toBeNull();

    Queue::assertPushed(IndexCurriculumDocumentJob::class, fn (IndexCurriculumDocumentJob $job): bool => $job->document->is($document));
    Queue::assertNotPushed(RemoveCurriculumDocumentFromIndexJob::class);
});

it('keeps the existing store entry live while a retry is queued', function (): void {
    Queue::fake();

    $document = CurriculumDocument::factory()->published()->create([
        'gemini_document_name' => 'fileSearchStores/glc-store/documents/stale',
    ]);

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.reindex', $document))
        ->assertRedirect();

    expect($document->refresh()->gemini_document_name)->toBe('fileSearchStores/glc-store/documents/stale');

    Queue::assertNotPushed(RemoveCurriculumDocumentFromIndexJob::class);
    Queue::assertPushed(IndexCurriculumDocumentJob::class);
});

it('rejects reindexing a draft document', function (): void {
    Queue::fake();

    $document = CurriculumDocument::factory()->create();

    $this->actingAs($this->supervisor)
        ->postJson(route('curriculum.documents.reindex', $document))
        ->assertStatus(422);

    Queue::assertNothingPushed();
});

it('filters the document list by status and hierarchy', function (): void {
    $draft = CurriculumDocument::factory()->create(['title' => 'Draft doc']);
    $published = CurriculumDocument::factory()->published()->create(['title' => 'Published doc']);

    $this->actingAs($this->supervisor)
        ->get(route('curriculum.index', ['status' => 'published']))
        ->assertInertia(fn ($page) => $page
            ->where('documents.total', 1)
            ->where('documents.data.0.title', 'Published doc'));

    $this->actingAs($this->supervisor)
        ->get(route('curriculum.index', ['course_id' => $draft->course_id]))
        ->assertInertia(fn ($page) => $page
            ->where('documents.total', 1)
            ->where('documents.data.0.title', 'Draft doc'));
});
