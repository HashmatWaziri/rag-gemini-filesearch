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

final readonly class LessonMaterialsUploadController
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
            ...$this->hierarchyRules($request, requireLesson: true),
            'material_kinds' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'material_kinds.*' => $this->materialKindRules(),
            'titles' => ['nullable', 'array', 'max:'.$maxFiles],
            'titles.*' => ['nullable', 'string', 'max:255'],
            'files' => ['required', 'array', 'min:1', 'max:'.$maxFiles],
            'files.*' => ['required', 'file'],
        ]);

        $kinds = $validated['material_kinds'];
        /** @var list<UploadedFile> $files */
        $files = $validated['files'];
        /** @var list<string|null> $titles */
        $titles = $validated['titles'] ?? [];

        if (count($kinds) !== count($files)) {
            throw ValidationException::withMessages([
                'files' => 'Each tagged row needs a file and a material tag.',
            ]);
        }

        $this->limits->assertBatchSize(count($files));
        $this->limits->assertNoDuplicateFilenames($files);

        $lessonId = (int) $validated['course_lesson_id'];
        $this->limits->assertLessonCapacity($lessonId, count($files));

        $attributes = [
            'course_id' => (int) $validated['course_id'],
            'course_level_id' => (int) $validated['course_level_id'],
            'course_unit_id' => (int) $validated['course_unit_id'],
            'course_lesson_id' => $lessonId,
        ];

        /** @var User $user */
        $user = $request->user();

        $report = [];
        $succeeded = 0;

        foreach ($files as $index => $file) {
            $kind = CurriculumMaterialKind::from($kinds[$index]);
            $title = isset($titles[$index]) && is_string($titles[$index]) && $titles[$index] !== ''
                ? $titles[$index]
                : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            try {
                $document = $this->uploads->store($file, [
                    ...$attributes,
                    'title' => $title,
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
            ->route('curriculum.index', [
                'course_id' => $attributes['course_id'],
                'course_level_id' => $attributes['course_level_id'],
                'course_unit_id' => $attributes['course_unit_id'],
                'course_lesson_id' => $attributes['course_lesson_id'],
            ])
            ->with('bulk_report', $report)
            ->with('status', sprintf(
                '%d of %d tagged lesson material(s) uploaded as drafts.',
                $succeeded,
                count($files),
            ));
    }
}
