<?php

declare(strict_types=1);

namespace App\Services\Glc\Curriculum;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Models\Glc\CurriculumDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final readonly class CurriculumUploadService
{
    public function __construct(private TextExtractor $extractor) {}

    /**
     * @param  array{course_id: int, course_level_id: int, course_unit_id: int, course_lesson_id: int|null, title: string}  $attributes
     */
    public function store(UploadedFile $file, array $attributes, User $uploader): CurriculumDocument
    {
        [$path, $format, $text] = $this->storeAndExtract($file, $attributes['course_id']);

        return CurriculumDocument::query()->create([
            ...$attributes,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'format' => $format,
            'extracted_text' => $text,
            'status' => CurriculumDocumentStatus::Draft,
            'version' => 1,
            'uploaded_by' => $uploader->id,
            'index_status' => CurriculumIndexStatus::Pending,
        ]);
    }

    public function replace(CurriculumDocument $document, UploadedFile $file): CurriculumDocument
    {
        [$path, $format, $text] = $this->storeAndExtract($file, $document->course_id);

        $previousPath = $document->file_path;

        $document->update([
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'format' => $format,
            'extracted_text' => $text,
            'status' => CurriculumDocumentStatus::Draft,
            'version' => $document->version + 1,
            'published_at' => null,
            'archived_at' => null,
            'gemini_file_name' => null,
            'gemini_document_name' => null,
            'index_status' => CurriculumIndexStatus::Pending,
            'index_error' => null,
        ]);

        if ($previousPath !== $path) {
            Storage::disk('local')->delete($previousPath);
        }

        return $document->refresh();
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function storeAndExtract(UploadedFile $file, int $courseId): array
    {
        $format = mb_strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs('glc/curriculum/'.$courseId, $file->hashName(), 'local');

        if (! is_string($path)) {
            throw new RuntimeException('Unable to store the uploaded file.');
        }

        try {
            $text = $this->extractor->extract(Storage::disk('local')->path($path), $format);
        } catch (Throwable $throwable) {
            Storage::disk('local')->delete($path);

            throw new RuntimeException(
                sprintf('Text extraction failed for %s: %s', $file->getClientOriginalName(), $throwable->getMessage()),
                previous: $throwable,
            );
        }

        return [$path, $format, $text];
    }
}
