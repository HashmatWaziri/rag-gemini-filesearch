<?php

declare(strict_types=1);

namespace App\Services\Glc\Curriculum;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Enums\Glc\CurriculumMaterialKind;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\CurriculumDocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class CurriculumUploadService
{
    private const array AUDIO_EXTENSIONS = ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'opus', 'flac', 'mpga', 'wma'];

    private const array VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'm4v', 'wmv'];

    private const array IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'bmp', 'svg', 'tiff'];

    private const array PRESENTATION_EXTENSIONS = ['ppt', 'pptx', 'key', 'odp'];

    /**
     * @param  array{course_id: int, course_level_id: int, course_unit_id: int, course_lesson_id: int|null, title: string, material_kind: CurriculumMaterialKind}  $attributes
     */
    public function store(UploadedFile $file, array $attributes, User $uploader): CurriculumDocument
    {
        $this->assertAcceptable($file);

        [$path, $format] = $this->storeFile($file, $attributes['course_id']);

        return CurriculumDocument::query()->create([
            ...$attributes,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'format' => $format,
            'extracted_text' => null,
            'status' => CurriculumDocumentStatus::Draft,
            'version' => 1,
            'uploaded_by' => $uploader->id,
            'index_status' => CurriculumIndexStatus::Pending,
        ]);
    }

    public function replace(CurriculumDocument $document, UploadedFile $file): CurriculumDocument
    {
        $this->assertAcceptable($file);

        $this->snapshotCurrentVersion($document);

        [$path, $format] = $this->storeFile($file, $document->course_id);

        $document->update([
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'format' => $format,
            'extracted_text' => null,
            'status' => CurriculumDocumentStatus::Draft,
            'version' => $document->version + 1,
            'published_at' => null,
            'archived_at' => null,
            'index_status' => CurriculumIndexStatus::Pending,
            'index_error' => null,
        ]);

        return $document->refresh();
    }

    public function restoreFromVersion(CurriculumDocument $document, int $versionNumber): CurriculumDocument
    {
        $version = CurriculumDocumentVersion::query()
            ->where('curriculum_document_id', $document->id)
            ->where('version', $versionNumber)
            ->firstOrFail();

        if (! Storage::disk('local')->exists($version->file_path)) {
            throw new RuntimeException('The archived file for this version is missing.');
        }

        $this->snapshotCurrentVersion($document);

        [$path, $format] = $this->copyStoredFile(
            $version->file_path,
            $document->course_id,
            $version->format,
        );

        $document->update([
            'original_filename' => $version->original_filename,
            'file_path' => $path,
            'format' => $format,
            'extracted_text' => $version->extracted_text,
            'status' => CurriculumDocumentStatus::Draft,
            'version' => $document->version + 1,
            'published_at' => null,
            'archived_at' => null,
            'index_status' => CurriculumIndexStatus::Pending,
            'index_error' => null,
        ]);

        return $document->refresh();
    }

    public function versionedFilePath(int $courseId, int $documentId, int $version, string $format): string
    {
        $extension = $format !== '' ? '.'.$format : '';

        return sprintf(
            'glc/curriculum/%d/documents/%d/v%d%s',
            $courseId,
            $documentId,
            $version,
            $extension,
        );
    }

    public function fileError(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            $message = $file->getErrorMessage();

            return $message !== '' ? $message : 'The file did not upload correctly. Select it again and retry.';
        }

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

    private function snapshotCurrentVersion(CurriculumDocument $document): void
    {
        $versionPath = $this->versionedFilePath(
            $document->course_id,
            $document->id,
            $document->version,
            $document->format,
        );

        $storedPath = $document->file_path;

        if (Storage::disk('local')->exists($storedPath)) {
            Storage::disk('local')->move($storedPath, $versionPath);
            $storedPath = $versionPath;
        }

        CurriculumDocumentVersion::query()->create([
            'curriculum_document_id' => $document->id,
            'version' => $document->version,
            'title' => $document->title,
            'material_kind' => $document->material_kind,
            'original_filename' => $document->original_filename,
            'file_path' => $storedPath,
            'format' => $document->format,
            'extracted_text' => $document->extracted_text,
            'status' => $document->status,
            'published_at' => $document->published_at,
            'archived_at' => $document->archived_at,
            'gemini_file_name' => $document->gemini_file_name,
            'gemini_document_name' => $document->gemini_document_name,
            'uploaded_by' => $document->uploaded_by,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function copyStoredFile(string $sourcePath, int $courseId, string $format): array
    {
        $contents = Storage::disk('local')->get($sourcePath);

        if ($contents === null) {
            throw new RuntimeException(sprintf('Stored file [%s] is missing.', $sourcePath));
        }

        $format = mb_strtolower($format);
        $filename = Str::random(40).($format !== '' ? '.'.$format : '');
        $relativePath = 'glc/curriculum/'.$courseId.'/'.$filename;

        if (! Storage::disk('local')->put($relativePath, $contents)) {
            throw new RuntimeException('Unable to store the restored file.');
        }

        return [$relativePath, $format];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function storeFile(UploadedFile $file, int $courseId): array
    {
        $format = mb_strtolower($file->getClientOriginalExtension());
        $filename = Str::random(40).($format !== '' ? '.'.$format : '');
        $relativePath = 'glc/curriculum/'.$courseId.'/'.$filename;

        $stream = fopen($file->getPathname(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('Unable to read the uploaded file.');
        }

        try {
            $stored = Storage::disk('local')->put($relativePath, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if (! $stored) {
            throw new RuntimeException('Unable to store the uploaded file.');
        }

        return [$relativePath, $format];
    }
}
