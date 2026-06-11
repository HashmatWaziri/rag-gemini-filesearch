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
            'preview_confirmed.required' => 'Please check the text preview and confirm it looks right before publishing.',
            'preview_confirmed.accepted' => 'Please check the text preview and confirm it looks right before publishing.',
        ]);

        if ($document->status !== CurriculumDocumentStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only drafts can be published.',
            ]);
        }

        if (mb_trim((string) $document->extracted_text) === '') {
            throw ValidationException::withMessages([
                'status' => 'No readable text was found in this file, so the AI Tutor can\'t use it. Replace the file with a readable PDF, Word, or text document first.',
            ]);
        }

        $document->update([
            'status' => CurriculumDocumentStatus::Publishing,
            'index_status' => CurriculumIndexStatus::Pending,
            'index_error' => null,
        ]);

        $this->auditLogger->log(AuditAction::CurriculumPublished, $request->user(), $document, [
            'title' => $document->title,
            'version' => $document->version,
        ]);

        IndexCurriculumDocumentJob::dispatch($document);

        return back()->with('status', 'Publishing started. This document will be available to the AI Tutor shortly.');
    }
}
