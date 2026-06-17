<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Http\Controllers\Glc\Curriculum\Concerns\AuthorizesCurriculum;
use App\Jobs\Glc\Curriculum\IndexCurriculumDocumentJob;
use App\Models\Glc\CurriculumDocument;
use App\Services\Glc\AuditLogger;
use App\Services\Glc\Curriculum\CurriculumPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final readonly class PublishDocumentController
{
    use AuthorizesCurriculum;

    public function __construct(private AuditLogger $auditLogger) {}

    public function __invoke(Request $request, CurriculumDocument $document): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::Publish);

        $request->validate([
            'preview_confirmed' => ['required', 'accepted'],
        ], [
            'preview_confirmed.required' => 'Please confirm the file details before publishing.',
            'preview_confirmed.accepted' => 'Please confirm the file details before publishing.',
        ]);

        if ($document->status !== CurriculumDocumentStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Only drafts can be published.',
            ]);
        }

        if (! Storage::disk('local')->exists($document->file_path)) {
            throw ValidationException::withMessages([
                'status' => 'The stored file is missing. Replace the file, then publish again.',
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

        return back()->with('status', 'Publishing started. The file will be uploaded to the AI Tutor shortly.');
    }
}
