<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Jobs\Glc\Curriculum\IndexCurriculumDocumentJob;
use App\Models\Glc\CurriculumDocument;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final readonly class PublishDocumentController
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function __invoke(Request $request, CurriculumDocument $document): RedirectResponse
    {
        $request->validate([
            'preview_confirmed' => ['required', 'accepted'],
        ], [
            'preview_confirmed.required' => 'Review the extracted text preview and confirm it before publishing.',
            'preview_confirmed.accepted' => 'Review the extracted text preview and confirm it before publishing.',
        ]);

        if ($document->status !== CurriculumDocumentStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only draft documents can be published.',
            ]);
        }

        $document->update([
            'status' => CurriculumDocumentStatus::Published,
            'published_at' => now(),
            'index_status' => CurriculumIndexStatus::Pending,
            'index_error' => null,
        ]);

        $this->auditLogger->log(AuditAction::CurriculumPublished, $request->user(), $document, [
            'title' => $document->title,
            'version' => $document->version,
        ]);

        IndexCurriculumDocumentJob::dispatch($document);

        return back()->with('status', 'Document published. Indexing for tutor retrieval has been queued.');
    }
}
