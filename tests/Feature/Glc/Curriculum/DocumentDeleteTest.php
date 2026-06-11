<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Models\Glc\CurriculumDocument;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
});

it('forbids academic supervisors from hard deleting documents', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $document = CurriculumDocument::factory()->create();

    $this->actingAs($supervisor)
        ->delete(route('curriculum.documents.destroy', $document))
        ->assertForbidden();

    expect(CurriculumDocument::query()->find($document->id))->not->toBeNull();
});

it('lets an admin hard delete a document, its file, and its store entry, with audit', function (): void {
    config(['gemini.api_key' => 'test-key']);

    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/documents/doc-9' => Http::response([]),
    ]);

    $admin = User::factory()->admin()->create();
    $document = CurriculumDocument::factory()->published()->create([
        'file_path' => 'glc/curriculum/1/to-delete.txt',
        'gemini_document_name' => 'fileSearchStores/glc-store/documents/doc-9',
    ]);

    Storage::disk('local')->put($document->file_path, 'content');

    $this->actingAs($admin)
        ->delete(route('curriculum.documents.destroy', $document))
        ->assertRedirect(route('curriculum.index'));

    expect(CurriculumDocument::query()->find($document->id))->toBeNull();
    Storage::disk('local')->assertMissing('glc/curriculum/1/to-delete.txt');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE'
        && $request->url() === 'https://generativelanguage.googleapis.com/v1beta/fileSearchStores/glc-store/documents/doc-9');

    $audit = AuditLog::query()->where('action', AuditAction::CurriculumDeleted->value)->firstOrFail();

    expect($audit->actor_id)->toBe($admin->id)
        ->and($audit->subject_id)->toBe($document->id)
        ->and($audit->details['title'])->toBe($document->title);
});

it('hard deletes an unindexed draft without calling the API', function (): void {
    $admin = User::factory()->admin()->create();
    $document = CurriculumDocument::factory()->create([
        'file_path' => 'glc/curriculum/1/draft.txt',
    ]);

    Storage::disk('local')->put($document->file_path, 'content');

    $this->actingAs($admin)
        ->delete(route('curriculum.documents.destroy', $document))
        ->assertRedirect(route('curriculum.index'));

    expect(CurriculumDocument::query()->find($document->id))->toBeNull();
    Storage::disk('local')->assertMissing('glc/curriculum/1/draft.txt');
});
