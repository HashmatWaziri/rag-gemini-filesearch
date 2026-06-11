<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Jobs\Glc\Curriculum\IndexCurriculumDocumentJob;
use App\Jobs\Glc\Curriculum\RemoveCurriculumDocumentFromIndexJob;
use App\Models\Glc\CurriculumDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final readonly class ReindexDocumentController
{
    public function __invoke(Request $request, CurriculumDocument $document): RedirectResponse
    {
        if ($document->status !== CurriculumDocumentStatus::Published) {
            throw ValidationException::withMessages([
                'status' => 'Only published documents can be reindexed.',
            ]);
        }

        if ($document->index_status === CurriculumIndexStatus::Indexing) {
            throw ValidationException::withMessages([
                'status' => 'Indexing is already in progress.',
            ]);
        }

        $staleDocumentName = $document->gemini_document_name;

        $document->update([
            'index_status' => CurriculumIndexStatus::Pending,
            'index_error' => null,
            'gemini_file_name' => null,
            'gemini_document_name' => null,
        ]);

        if ($staleDocumentName !== null) {
            RemoveCurriculumDocumentFromIndexJob::dispatch($document, $staleDocumentName);
        }

        IndexCurriculumDocumentJob::dispatch($document);

        return back()->with('status', 'Reindexing has been queued.');
    }
}
