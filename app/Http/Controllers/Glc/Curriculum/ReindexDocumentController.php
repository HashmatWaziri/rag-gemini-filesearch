<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Http\Controllers\Glc\Curriculum\Concerns\AuthorizesCurriculum;
use App\Jobs\Glc\Curriculum\IndexCurriculumDocumentJob;
use App\Models\Glc\CurriculumDocument;
use App\Services\Glc\Curriculum\CurriculumPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final readonly class ReindexDocumentController
{
    use AuthorizesCurriculum;

    public function __invoke(Request $request, CurriculumDocument $document): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::Reindex);

        if (! in_array($document->status, [CurriculumDocumentStatus::Published, CurriculumDocumentStatus::PublishFailed], true)) {
            throw ValidationException::withMessages([
                'status' => 'This document is not published, so there is nothing to retry.',
            ]);
        }

        if ($document->index_status === CurriculumIndexStatus::Indexing
            || $document->status === CurriculumDocumentStatus::Publishing) {
            throw ValidationException::withMessages([
                'status' => 'This document is already being prepared for the AI Tutor.',
            ]);
        }

        $updates = [
            'index_status' => CurriculumIndexStatus::Pending,
            'index_error' => null,
        ];

        if ($document->status === CurriculumDocumentStatus::PublishFailed) {
            $updates['status'] = CurriculumDocumentStatus::Publishing;
        }

        $document->update($updates);

        IndexCurriculumDocumentJob::dispatch($document);

        return back()->with('status', 'Trying again — this document will be available to the AI Tutor shortly.');
    }
}
