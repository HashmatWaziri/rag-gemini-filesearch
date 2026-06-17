<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Enums\Glc\CurriculumMaterialKind;
use App\Http\Concerns\Glc\ValidatesHierarchy;
use App\Http\Controllers\Glc\Curriculum\Concerns\AuthorizesCurriculum;
use App\Models\User;
use App\Services\Glc\Curriculum\CurriculumPermission;
use App\Services\Glc\Curriculum\CurriculumUploadLimits;
use App\Services\Glc\Curriculum\CurriculumUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class BulkUploadController
{
    use AuthorizesCurriculum;
    use ValidatesHierarchy;

    public function __construct(
        private CurriculumUploadService $uploads,
        private CurriculumUploadLimits $limits,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::Upload);

        $maxFiles = $this->limits->maxMaterialsPerRequest();

        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'files.*' => ['required', 'file'],
            'material_kinds' => ['nullable', 'array', 'max:'.$maxFiles],
            'material_kinds.*' => $this->materialKindRules(),
            ...$this->hierarchyRules($request),
        ]);

        /** @var list<UploadedFile> $files */
        $files = $validated['files'];
        /** @var list<string|null> $materialKinds */
        $materialKinds = $validated['material_kinds'] ?? [];

        if ($materialKinds !== [] && count($materialKinds) !== count($files)) {
            throw ValidationException::withMessages([
                'material_kinds' => 'Provide one material tag per file, or leave tags blank to default to Other.',
            ]);
        }

        $this->limits->assertBatchSize(count($files));
        $this->limits->assertNoDuplicateFilenames($files);

        $attributes = [
            'course_id' => (int) $validated['course_id'],
            'course_level_id' => (int) $validated['course_level_id'],
            'course_unit_id' => (int) $validated['course_unit_id'],
            'course_lesson_id' => isset($validated['course_lesson_id']) ? (int) $validated['course_lesson_id'] : null,
        ];

        if ($attributes['course_lesson_id'] !== null) {
            $this->limits->assertLessonCapacity($attributes['course_lesson_id'], count($files));
        }

        /** @var User $user */
        $user = $request->user();

        $report = [];
        $succeeded = 0;

        foreach ($files as $index => $file) {
            $kind = isset($materialKinds[$index]) && is_string($materialKinds[$index])
                ? CurriculumMaterialKind::from($materialKinds[$index])
                : CurriculumMaterialKind::Other;

            try {
                $document = $this->uploads->store($file, [
                    ...$attributes,
                    'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'material_kind' => $kind,
                ], $user);

                $report[] = [
                    'filename' => $file->getClientOriginalName(),
                    'success' => true,
                    'error' => null,
                    'document_id' => $document->id,
                    'material_kind' => $kind->value,
                ];
                $succeeded++;
            } catch (Throwable $throwable) {
                $report[] = [
                    'filename' => $file->getClientOriginalName(),
                    'success' => false,
                    'error' => $throwable->getMessage(),
                    'document_id' => null,
                    'material_kind' => $kind->value,
                ];
            }
        }

        return redirect()
            ->route('curriculum.index')
            ->with('bulk_report', $report)
            ->with('status', sprintf('%d of %d files uploaded as drafts.', $succeeded, count($files)));
    }
}
