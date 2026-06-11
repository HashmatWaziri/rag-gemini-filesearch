<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Http\Concerns\Glc\ValidatesHierarchy;
use App\Models\User;
use App\Services\Glc\Curriculum\CurriculumUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Throwable;

final readonly class BulkUploadController
{
    use ValidatesHierarchy;

    public function __construct(private CurriculumUploadService $uploads) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:'.config()->integer('glc.curriculum.max_bulk_files')],
            'files.*' => ['required', 'file'],
            ...$this->hierarchyRules($request),
        ]);

        $attributes = [
            'course_id' => (int) $validated['course_id'],
            'course_level_id' => (int) $validated['course_level_id'],
            'course_unit_id' => (int) $validated['course_unit_id'],
            'course_lesson_id' => isset($validated['course_lesson_id']) ? (int) $validated['course_lesson_id'] : null,
        ];

        /** @var User $user */
        $user = $request->user();
        /** @var list<UploadedFile> $files */
        $files = $validated['files'];

        $report = [];
        $succeeded = 0;

        foreach ($files as $file) {
            try {
                $document = $this->uploads->store($file, [
                    ...$attributes,
                    'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                ], $user);

                $report[] = [
                    'filename' => $file->getClientOriginalName(),
                    'success' => true,
                    'error' => null,
                    'document_id' => $document->id,
                ];
                $succeeded++;
            } catch (Throwable $throwable) {
                $report[] = [
                    'filename' => $file->getClientOriginalName(),
                    'success' => false,
                    'error' => $throwable->getMessage(),
                    'document_id' => null,
                ];
            }
        }

        return redirect()
            ->route('curriculum.index')
            ->with('bulk_report', $report)
            ->with('status', sprintf('%d of %d files uploaded as drafts.', $succeeded, count($files)));
    }
}
