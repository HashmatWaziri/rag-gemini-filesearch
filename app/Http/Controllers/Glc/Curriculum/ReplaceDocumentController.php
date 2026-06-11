<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\CurriculumDocumentStatus;
use App\Models\Glc\CurriculumDocument;
use App\Services\Glc\AuditLogger;
use App\Services\Glc\Curriculum\CurriculumUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final readonly class ReplaceDocumentController
{
    public function __construct(
        private CurriculumUploadService $uploads,
        private AuditLogger $auditLogger,
    ) {}

    public function __invoke(Request $request, CurriculumDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file'],
        ]);

        $wasLive = $document->status === CurriculumDocumentStatus::Published
            && $document->gemini_document_name !== null;
        $previousVersion = $document->version;

        /** @var UploadedFile $file */
        $file = $validated['file'];

        try {
            $document = $this->uploads->replace($document, $file);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        $this->auditLogger->log(AuditAction::CurriculumReplaced, $request->user(), $document, [
            'title' => $document->title,
            'previous_version' => $previousVersion,
            'version' => $document->version,
            'original_filename' => $document->original_filename,
        ]);

        $message = sprintf(
            'New file saved as version %d. The document is back in draft — check the new text preview, then publish it to the AI Tutor.',
            $document->version,
        );

        if ($wasLive) {
            $message .= ' Until then, students keep the previous published version.';
        }

        return back()->with('status', $message);
    }
}
