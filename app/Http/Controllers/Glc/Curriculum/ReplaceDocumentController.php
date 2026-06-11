<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Enums\Glc\AuditAction;
use App\Http\Concerns\Glc\ValidatesHierarchy;
use App\Jobs\Glc\Curriculum\RemoveCurriculumDocumentFromIndexJob;
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
    use ValidatesHierarchy;

    public function __construct(
        private CurriculumUploadService $uploads,
        private AuditLogger $auditLogger,
    ) {}

    public function __invoke(Request $request, CurriculumDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', ...$this->fileRules()],
        ]);

        $staleDocumentName = $document->gemini_document_name;
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

        if ($staleDocumentName !== null) {
            RemoveCurriculumDocumentFromIndexJob::dispatch($document, $staleDocumentName);
        }

        return back()->with('status', sprintf(
            'File replaced as version %d. The document is back in draft: review the new extracted text and publish again to reindex.',
            $document->version,
        ));
    }
}
