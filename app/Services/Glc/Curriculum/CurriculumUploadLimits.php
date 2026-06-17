<?php

declare(strict_types=1);

namespace App\Services\Glc\Curriculum;

use App\Models\Glc\CurriculumDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final readonly class CurriculumUploadLimits
{
    public function maxMaterialsPerRequest(): int
    {
        return config()->integer('glc.curriculum.max_bulk_files');
    }

    public function maxDocumentsPerLesson(): int
    {
        return config()->integer('glc.curriculum.max_documents_per_lesson');
    }

    public function uploadsPerMinute(): int
    {
        return config()->integer('glc.curriculum.uploads_per_minute');
    }

    public function documentsForLesson(int $lessonId): int
    {
        return CurriculumDocument::query()
            ->where('course_lesson_id', $lessonId)
            ->count();
    }

    public function remainingLessonSlots(int $lessonId): int
    {
        return max(0, $this->maxDocumentsPerLesson() - $this->documentsForLesson($lessonId));
    }

    /**
     * @return array{
     *     existing_count: int,
     *     max_per_lesson: int,
     *     max_per_request: int,
     *     remaining_slots: int,
     *     max_rows: int
     * }
     */
    public function capacityForLesson(int $lessonId): array
    {
        $existing = $this->documentsForLesson($lessonId);
        $maxPerLesson = $this->maxDocumentsPerLesson();
        $maxPerRequest = $this->maxMaterialsPerRequest();
        $remaining = max(0, $maxPerLesson - $existing);

        return [
            'existing_count' => $existing,
            'max_per_lesson' => $maxPerLesson,
            'max_per_request' => $maxPerRequest,
            'remaining_slots' => $remaining,
            'max_rows' => min($remaining, $maxPerRequest),
        ];
    }

    public function assertBatchSize(int $count): void
    {
        if ($count < 1) {
            throw ValidationException::withMessages([
                'files' => 'Add at least one tagged file.',
            ]);
        }

        if ($count > $this->maxMaterialsPerRequest()) {
            throw ValidationException::withMessages([
                'files' => sprintf(
                    'You can upload at most %d file(s) per request.',
                    $this->maxMaterialsPerRequest(),
                ),
            ]);
        }
    }

    public function assertLessonCapacity(int $lessonId, int $addingCount): void
    {
        if ($addingCount < 1) {
            return;
        }

        $existing = $this->documentsForLesson($lessonId);
        $remaining = max(0, $this->maxDocumentsPerLesson() - $existing);

        if ($addingCount > $remaining) {
            throw ValidationException::withMessages([
                'files' => sprintf(
                    'This lesson already has %d document(s). You can add at most %d more (limit %d per lesson).',
                    $existing,
                    $remaining,
                    $this->maxDocumentsPerLesson(),
                ),
            ]);
        }
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function assertNoDuplicateFilenames(array $files): void
    {
        $seen = [];

        foreach ($files as $index => $file) {
            $key = mb_strtolower($file->getClientOriginalName());

            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    "files.{$index}" => sprintf(
                        'Duplicate file "%s" in this upload batch.',
                        $file->getClientOriginalName(),
                    ),
                ]);
            }

            $seen[$key] = true;
        }
    }
}
