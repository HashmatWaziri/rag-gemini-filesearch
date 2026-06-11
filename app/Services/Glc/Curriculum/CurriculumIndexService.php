<?php

declare(strict_types=1);

namespace App\Services\Glc\Curriculum;

use App\Data\GeminiFileSearchStoreData;
use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Enums\SettingKey;
use App\Models\Glc\CurriculumDocument;
use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\File;
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

    public function isConfigured(): bool
    {
        return $this->apiKey() !== null;
    }

    public function index(CurriculumDocument $document): void
    {
        if (! in_array($document->status, [
            CurriculumDocumentStatus::Publishing,
            CurriculumDocumentStatus::Published,
            CurriculumDocumentStatus::PublishFailed,
        ], true)) {
            return;
        }

        if (! $this->isConfigured()) {
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

        $previousDocumentName = $document->gemini_document_name;

        try {
            $storeName = $this->ensureStore();
            $fileName = $this->uploadFile($document);
            $documentName = $this->importToStore($storeName, $fileName, $document);

            if ($previousDocumentName !== null && $documentName !== null && $previousDocumentName !== $documentName) {
                $this->deleteStoreDocumentQuietly($previousDocumentName);
            }

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

    public function removeFromIndex(CurriculumDocument $document): void
    {
        $name = $document->gemini_document_name;
        $error = $name === null ? null : $this->attemptDelete($name);

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

    public function ensureStore(?string $displayName = null): string
    {
        $existing = $this->storedStoreName();

        if ($existing !== null) {
            $this->rememberStoreName($existing);

            return $existing;
        }

        $response = $this->client()->post($this->baseUrl().'/fileSearchStores', [
            'displayName' => $displayName ?? config()->string('glc.curriculum.store_display_name'),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Failed to create FileSearch store: '.$response->body());
        }

        $name = $response->json('name');

        if (! is_string($name) || $name === '') {
            throw new RuntimeException('FileSearch store creation returned no store name.');
        }

        $this->rememberStoreName($name);

        return $name;
    }

    public function getStoreStatus(string $storeName): ?GeminiFileSearchStoreData
    {
        $response = $this->client()->get(sprintf('%s/%s', $this->baseUrl(), $storeName));

        if ($response->failed()) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = (array) $response->json();

        $data['activeDocumentsCount'] ??= 0;
        $data['pendingDocumentsCount'] ??= 0;
        $data['failedDocumentsCount'] ??= 0;
        $data['sizeBytes'] ??= 0;

        return GeminiFileSearchStoreData::from($data);
    }

    public function uploadFile(CurriculumDocument $document): string
    {
        $contents = Storage::disk('local')->get($document->file_path);

        if ($contents === null) {
            throw new RuntimeException(sprintf('Stored file [%s] is missing.', $document->file_path));
        }

        return $this->uploadContents($contents, $document->original_filename, $document->title, $this->mimeType($document->format));
    }

    public function uploadLocalFile(string $path, string $displayName, ?string $mimeType = null): string
    {
        $contents = File::get($path);

        return $this->uploadContents(
            $contents,
            basename($path),
            $displayName,
            $mimeType ?? $this->mimeType(pathinfo($path, PATHINFO_EXTENSION)),
        );
    }

    public function importToStore(string $storeName, string $fileName, ?CurriculumDocument $document = null): ?string
    {
        $payload = ['file_name' => $fileName];

        if ($document !== null) {
            $payload['custom_metadata'] = $this->customMetadata($document);
        }

        $response = $this->client()->post(sprintf('%s/%s:importFile', $this->baseUrl(), $storeName), $payload);

        if ($response->failed()) {
            throw new RuntimeException('Failed to import file into store: '.$response->body());
        }

        $operationName = $response->json('name');

        if (! is_string($operationName) || $operationName === '') {
            throw new RuntimeException('Import returned no operation name.');
        }

        $operation = $this->waitForOperation($operationName);

        return $document === null ? null : $this->resolveDocumentName($operation, $storeName, $document);
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
        if ($documentName === null || ! $this->isConfigured()) {
            return;
        }

        try {
            $this->deleteStoreDocument($documentName);
        } catch (Throwable) {
            //
        }
    }

    private function storedStoreName(): ?string
    {
        foreach ([SettingKey::GlcCurriculumStoreName, SettingKey::GeminiFileSearchStoreName] as $key) {
            $value = Setting::get($key);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function rememberStoreName(string $name): void
    {
        foreach ([SettingKey::GlcCurriculumStoreName, SettingKey::GeminiFileSearchStoreName] as $key) {
            if (Setting::get($key) !== $name) {
                Setting::set($key, $name);
            }
        }
    }

    private function uploadContents(string $contents, string $filename, string $displayName, string $mimeType): string
    {
        $metadata = json_encode(['file' => ['displayName' => $displayName]]);

        $response = Http::withHeaders([
            'x-goog-api-key' => $this->apiKey(),
            'X-Goog-Upload-Protocol' => 'multipart',
        ])
            ->timeout(config()->integer('gemini.request_timeout', 30))
            ->attach('metadata', (string) $metadata, 'metadata', ['Content-Type' => 'application/json'])
            ->attach('file', $contents, $filename, ['Content-Type' => $mimeType])
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
            'json' => 'application/json',
            default => 'application/octet-stream',
        };
    }

    private function attemptDelete(string $documentName): ?string
    {
        if (! $this->isConfigured()) {
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
