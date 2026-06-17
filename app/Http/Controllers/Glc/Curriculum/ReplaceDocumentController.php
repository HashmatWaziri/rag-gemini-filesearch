<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Enums\Glc\AuditAction;
use App\Http\Controllers\Glc\Curriculum\Concerns\AuthorizesCurriculum;
use App\Models\Glc\CurriculumDocument;
use App\Services\Glc\AuditLogger;
use App\Services\Glc\Curriculum\CurriculumPermission;
use App\Services\Glc\Curriculum\CurriculumUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final readonly class ReplaceDocumentController
{
    use AuthorizesCurriculum;

    public function __construct(
        private CurriculumUploadService $uploads,
        private AuditLogger $auditLogger,
    ) {}

    public function __invoke(Request $request, CurriculumDocument $document): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::Replace);

        $validated = $request->validate([
            'file' => ['required', 'file'],
        ]);

        $wasLive = $document->isTutorRetrievable();
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
            'New file saved as version %d. The document is back in draft — review the file details, then publish it to the AI Tutor.',
            $document->version,
        );

        if ($wasLive) {
            $message .= ' Until then, students keep the previous published version.';
        }

        return back()->with('status', $message);
    }
}
