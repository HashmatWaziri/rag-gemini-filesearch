<?php

declare(strict_types=1);

namespace App\Services\Glc\Curriculum;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Enums\SettingKey;
use App\Models\Glc\CurriculumDocument;
use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class CurriculumIndexService
{
    private const string UPLOAD_URL = 'https://generativelanguage.googleapis.com/upload/v1beta/files';

    private const int MAX_POLL_ATTEMPTS = 30;

    public function index(CurriculumDocument $document): void
    {
        if ($this->apiKey() === null) {
            $document->update([
                'index_status' => CurriculumIndexStatus::Failed,
                'index_error' => 'Gemini API key is not configured.',
            ]);

            return;
        }

        $document->update([
            'index_status' => CurriculumIndexStatus::Indexing,
            'index_error' => null,
        ]);

        try {
            $storeName = $this->ensureStore();
            $fileName = $this->uploadFile($document);
            $documentName = $this->importToStore($storeName, $fileName, $document);

            $document->update([
                'gemini_file_name' => $fileName,
                'gemini_document_name' => $documentName,
                'index_status' => CurriculumIndexStatus::Indexed,
                'index_error' => null,
            ]);
        } catch (Throwable $throwable) {
            $document->update([
                'index_status' => CurriculumIndexStatus::Failed,
                'index_error' => Str::limit($throwable->getMessage(), 1000),
            ]);
        }
    }

    public function removeFromIndex(CurriculumDocument $document, ?string $documentName = null): void
    {
        $isReplaceCleanup = $documentName !== null;
        $name = $documentName ?? $document->gemini_document_name;
        $error = $name === null ? null : $this->attemptDelete($name);

        if ($isReplaceCleanup) {
            return;
        }

        if ($error !== null) {
            $document->update([
                'index_status' => CurriculumIndexStatus::Failed,
                'index_error' => Str::limit($error, 1000),
            ]);

            return;
        }

        $document->update([
            'index_status' => CurriculumIndexStatus::Removed,
            'index_error' => null,
            'gemini_file_name' => null,
            'gemini_document_name' => null,
        ]);
    }

    public function ensureStore(): string
    {
        $existing = Setting::get(SettingKey::GlcCurriculumStoreName);

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $response = $this->client()->post($this->baseUrl().'/fileSearchStores', [
            'displayName' => config()->string('glc.curriculum.store_display_name'),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Failed to create FileSearch store: '.$response->body());
        }

        $name = $response->json('name');

        if (! is_string($name) || $name === '') {
            throw new RuntimeException('FileSearch store creation returned no store name.');
        }

        Setting::set(SettingKey::GlcCurriculumStoreName, $name);

        return $name;
    }

    public function uploadFile(CurriculumDocument $document): string
    {
        $contents = Storage::disk('local')->get($document->file_path);

        if ($contents === null) {
            throw new RuntimeException(sprintf('Stored file [%s] is missing.', $document->file_path));
        }

        $metadata = json_encode(['file' => ['displayName' => $document->title]]);

        $response = Http::withHeaders([
            'x-goog-api-key' => $this->apiKey(),
            'X-Goog-Upload-Protocol' => 'multipart',
        ])
            ->timeout(config()->integer('gemini.request_timeout', 30))
            ->attach('metadata', (string) $metadata, 'metadata', ['Content-Type' => 'application/json'])
            ->attach('file', $contents, $document->original_filename, ['Content-Type' => $this->mimeType($document->format)])
            ->post(self::UPLOAD_URL);

        if ($response->failed()) {
            throw new RuntimeException(sprintf('File upload failed: %d %s', $response->status(), $response->body()));
        }

        $name = $response->json('file.name');

        if (! is_string($name) || $name === '') {
            throw new RuntimeException('File upload returned no file name.');
        }

        return $name;
    }

    public function importToStore(string $storeName, string $fileName, CurriculumDocument $document): ?string
    {
        $response = $this->client()->post(sprintf('%s/%s:importFile', $this->baseUrl(), $storeName), [
            'file_name' => $fileName,
            'custom_metadata' => $this->customMetadata($document),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Failed to import file into store: '.$response->body());
        }

        $operationName = $response->json('name');

        if (! is_string($operationName) || $operationName === '') {
            throw new RuntimeException('Import returned no operation name.');
        }

        $operation = $this->waitForOperation($operationName);

        return $this->resolveDocumentName($operation, $storeName, $document);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function customMetadata(CurriculumDocument $document): array
    {
        $metadata = [
            ['key' => 'course_id', 'numericValue' => $document->course_id],
            ['key' => 'course_level_id', 'numericValue' => $document->course_level_id],
            ['key' => 'course_unit_id', 'numericValue' => $document->course_unit_id],
        ];

        if ($document->course_lesson_id !== null) {
            $metadata[] = ['key' => 'course_lesson_id', 'numericValue' => $document->course_lesson_id];
        }

        $metadata[] = ['key' => 'status', 'stringValue' => CurriculumDocumentStatus::Published->value];

        return $metadata;
    }

    public function deleteStoreDocument(string $documentName): bool
    {
        $response = $this->client()->delete(sprintf('%s/%s', $this->baseUrl(), $documentName));

        return $response->successful();
    }

    public function deleteStoreDocumentQuietly(?string $documentName): void
    {
        if ($documentName === null || $this->apiKey() === null) {
            return;
        }

        try {
            $this->deleteStoreDocument($documentName);
        } catch (Throwable) {
            //
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function waitForOperation(string $operationName): array
    {
        for ($attempt = 0; $attempt < self::MAX_POLL_ATTEMPTS; $attempt++) {
            $response = $this->client()->get(sprintf('%s/%s', $this->baseUrl(), $operationName));

            if ($response->failed()) {
                throw new RuntimeException('Failed to check import operation: '.$response->body());
            }

            if (! $response->json('done', false)) {
                Sleep::sleep(config()->integer('gemini.polling_interval', 10));

                continue;
            }

            $error = $response->json('error');

            if ($error !== null) {
                throw new RuntimeException('Import operation failed: '.json_encode($error));
            }

            /** @var array<string, mixed> */
            return $response->json() ?? [];
        }

        throw new RuntimeException('Import operation timed out.');
    }

    /**
     * @param  array<string, mixed>  $operation
     */
    private function resolveDocumentName(array $operation, string $storeName, CurriculumDocument $document): ?string
    {
        $response = is_array($operation['response'] ?? null) ? $operation['response'] : [];
        $name = $response['documentName'] ?? ($response['document']['name'] ?? null);

        if (is_string($name) && $name !== '') {
            return $name;
        }

        $listResponse = $this->client()->get(sprintf('%s/%s/documents', $this->baseUrl(), $storeName));

        if ($listResponse->failed()) {
            return null;
        }

        $documents = $listResponse->json('documents');

        if (! is_array($documents)) {
            return null;
        }

        foreach ($documents as $storeDocument) {
            if (! is_array($storeDocument)) {
                continue;
            }

            if (($storeDocument['displayName'] ?? null) === $document->title && is_string($storeDocument['name'] ?? null)) {
                return $storeDocument['name'];
            }
        }

        return null;
    }

    private function mimeType(string $format): string
    {
        return match (mb_strtolower($format)) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
    }

    private function attemptDelete(string $documentName): ?string
    {
        if ($this->apiKey() === null) {
            return 'Gemini API key is not configured; store document was not removed.';
        }

        try {
            return $this->deleteStoreDocument($documentName)
                ? null
                : sprintf('Failed to delete store document [%s].', $documentName);
        } catch (Throwable $throwable) {
            return $throwable->getMessage();
        }
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $this->apiKey(),
        ])->timeout(config()->integer('gemini.request_timeout', 30));
    }

    private function baseUrl(): string
    {
        return config()->string('gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
    }

    private function apiKey(): ?string
    {
        $key = config('gemini.api_key');

        return is_string($key) && $key !== '' ? $key : null;
    }
}
