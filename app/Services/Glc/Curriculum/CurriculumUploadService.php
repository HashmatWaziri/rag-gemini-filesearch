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
    private const array AUDIO_EXTENSIONS = ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'opus', 'flac', 'mpga', 'wma'];

    private const array VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'm4v', 'wmv'];

    private const array IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'bmp', 'svg', 'tiff'];

    private const array PRESENTATION_EXTENSIONS = ['ppt', 'pptx', 'key', 'odp'];

    public function __construct(private TextExtractor $extractor) {}

    /**
     * @param  array{course_id: int, course_level_id: int, course_unit_id: int, course_lesson_id: int|null, title: string}  $attributes
     */
    public function store(UploadedFile $file, array $attributes, User $uploader): CurriculumDocument
    {
        $this->assertAcceptable($file);

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
        $this->assertAcceptable($file);

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
            'index_status' => CurriculumIndexStatus::Pending,
            'index_error' => null,
        ]);

        if ($previousPath !== $path) {
            Storage::disk('local')->delete($previousPath);
        }

        return $document->refresh();
    }

    public function fileError(UploadedFile $file): ?string
    {
        $extension = mb_strtolower($file->getClientOriginalExtension());

        /** @var list<string> $allowed */
        $allowed = config('glc.curriculum.allowed_extensions', []);

        if (! in_array($extension, $allowed, true)) {
            return $this->unsupportedTypeMessage($extension);
        }

        $maxKb = config()->integer('glc.curriculum.max_file_size_kb');

        if ($file->getSize() > $maxKb * 1024) {
            return sprintf(
                'This file is larger than the %s size limit. Compress it or split it into smaller documents and upload each part.',
                $this->formatSize($maxKb),
            );
        }

        return null;
    }

    public function maxExtractedChars(): int
    {
        return config()->integer('glc.curriculum.max_extracted_chars', (int) env('GLC_CURRICULUM_MAX_EXTRACTED_CHARS', 500_000));
    }

    private function assertAcceptable(UploadedFile $file): void
    {
        $error = $this->fileError($file);

        if ($error !== null) {
            throw new RuntimeException($error);
        }
    }

    private function unsupportedTypeMessage(string $extension): string
    {
        $accepted = 'Please upload PDF, Word (.docx), or plain text (.txt) files.';

        if (in_array($extension, self::AUDIO_EXTENSIONS, true)) {
            return 'Audio files can\'t be added here — the AI Tutor works with documents only. Audio for listening and speaking belongs in Placement Test Content. '.$accepted;
        }

        if (in_array($extension, self::VIDEO_EXTENSIONS, true)) {
            return 'Video files can\'t be added here — the AI Tutor works with documents only. '.$accepted;
        }

        if (in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            return 'Images can\'t be added here. If this is a scanned page, convert it to a PDF with readable text first. '.$accepted;
        }

        if (in_array($extension, self::PRESENTATION_EXTENSIONS, true)) {
            return 'Presentation files can\'t be added here. Export the slides as a PDF and upload that instead.';
        }

        if ($extension === '') {
            return 'This file has no file type. '.$accepted;
        }

        return sprintf('".%s" files aren\'t supported. %s', $extension, $accepted);
    }

    private function formatSize(int $kilobytes): string
    {
        return $kilobytes >= 1024
            ? sprintf('%s MB', number_format($kilobytes / 1024, $kilobytes % 1024 === 0 ? 0 : 1))
            : sprintf('%d KB', $kilobytes);
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

            if ($throwable instanceof RuntimeException) {
                throw new RuntimeException(
                    sprintf('%s: %s', $file->getClientOriginalName(), $throwable->getMessage()),
                    previous: $throwable,
                );
            }

            throw new RuntimeException(
                sprintf(
                    'We couldn\'t read "%s". Check that the file opens correctly on your computer, then try uploading it again.',
                    $file->getClientOriginalName(),
                ),
                previous: $throwable,
            );
        }

        $maxChars = $this->maxExtractedChars();

        if (mb_strlen($text) > $maxChars) {
            Storage::disk('local')->delete($path);

            throw new RuntimeException(sprintf(
                'This document has too much text for the AI Tutor (over %s characters). Split it into smaller documents and upload each part.',
                number_format($maxChars),
            ));
        }

        return [$path, $format, $text];
    }
}
