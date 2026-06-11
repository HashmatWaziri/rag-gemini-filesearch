<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\CurriculumDocumentStatus;
use App\Jobs\Glc\Curriculum\RemoveCurriculumDocumentFromIndexJob;
use App\Models\Glc\CurriculumDocument;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final readonly class ArchiveDocumentController
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function __invoke(Request $request, CurriculumDocument $document): RedirectResponse
    {
        if ($document->status !== CurriculumDocumentStatus::Published) {
            throw ValidationException::withMessages([
                'status' => 'Only published documents can be archived.',
            ]);
        }

        $document->update([
            'status' => CurriculumDocumentStatus::Archived,
            'archived_at' => now(),
        ]);

        $this->auditLogger->log(AuditAction::CurriculumArchived, $request->user(), $document, [
            'title' => $document->title,
            'version' => $document->version,
        ]);

        RemoveCurriculumDocumentFromIndexJob::dispatch($document);

        return back()->with('status', 'Document archived. The AI Tutor will stop using it with students.');
    }
}
