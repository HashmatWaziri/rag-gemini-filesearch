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
use Illuminate\Validation\ValidationException;
use RuntimeException;

final readonly class RestoreDocumentVersionController
{
    use AuthorizesCurriculum;

    public function __construct(
        private CurriculumUploadService $uploads,
        private AuditLogger $auditLogger,
    ) {}

    public function __invoke(Request $request, CurriculumDocument $document, int $version): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::RestoreVersion);

        $wasLive = $document->isTutorRetrievable();
        $previousVersion = $document->version;

        try {
            $document = $this->uploads->restoreFromVersion($document, $version);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['status' => $exception->getMessage()]);
        }

        $this->auditLogger->log(AuditAction::CurriculumReplaced, $request->user(), $document, [
            'title' => $document->title,
            'previous_version' => $previousVersion,
            'version' => $document->version,
            'original_filename' => $document->original_filename,
            'restored_from_version' => $version,
        ]);

        $message = sprintf(
            'Version %d restored as version %d. The document is back in draft — review the file details, then publish it to the AI Tutor.',
            $version,
            $document->version,
        );

        if ($wasLive) {
            $message .= ' Until then, students keep the previous published version.';
        }

        return back()->with('status', $message);
    }
}
