<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\GeminiFileSearchStoreData;
use App\Enums\SettingKey;
use App\Models\Setting;
use App\Services\Glc\Curriculum\CurriculumIndexService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

#[Description('Upload document to Gemini File Search')]
#[Signature('upload:document-to-gemini-file-search
        {--file-path= : Path to the file to upload}
        {--display-name= : Display name for the uploaded file}
        {--store-name= : Display name for the file search store}')]
final class UploadDocumentToGeminiFileSearchCommand extends Command
{
    public function handle(CurriculumIndexService $service): void
    {
        $filePath = $this->resolveFilePath();

        if (! File::exists($filePath)) {
            $this->error('File not found: '.$filePath);

            return;
        }

        if (! $service->isConfigured()) {
            $this->error('Invalid API key configuration.');

            return;
        }

        $displayNameOption = $this->option('display-name');
        $displayName = is_string($displayNameOption) && $displayNameOption !== ''
            ? $displayNameOption
            : 'FoodData Central Foundation Food';

        try {
            $fileName = $service->uploadLocalFile($filePath, $displayName, 'application/json');
        } catch (Throwable $throwable) {
            $this->error('File upload failed: '.$throwable->getMessage());

            return;
        }

        $this->info('File uploaded successfully.');

        $hadStore = is_string(Setting::get(SettingKey::GeminiFileSearchStoreName))
            || is_string(Setting::get(SettingKey::GlcCurriculumStoreName));

        $storeNameOption = $this->option('store-name');

        try {
            $storeName = $service->ensureStore(
                is_string($storeNameOption) && $storeNameOption !== '' ? $storeNameOption : 'FoodData Central Store',
            );
        } catch (Throwable $throwable) {
            $this->error('Failed to create File Search store: '.$throwable->getMessage());

            return;
        }

        if (! $hadStore) {
            $this->info('File Search store created: '.$storeName);
        }

        $this->info('Using File Search store: '.$storeName);

        $storeData = $service->getStoreStatus($storeName);

        if (! $storeData instanceof GeminiFileSearchStoreData) {
            $this->warn('Unable to check store status.');
        }

        if ($storeData && $storeData->hasDocuments()) {
            $this->info(sprintf(
                'Store already contains documents: %d active, %d pending (%s MB)',
                $storeData->activeDocumentsCount,
                $storeData->pendingDocumentsCount,
                $storeData->getSizeMB(),
            ));
            $this->info('Skipping import.');

            return;
        }

        if ($storeData && $storeData->failedDocumentsCount > 0) {
            $this->warn(sprintf('Store has %d failed document(s). Proceeding with import...', $storeData->failedDocumentsCount));
        }

        try {
            $service->importToStore($storeName, $fileName);
        } catch (Throwable $throwable) {
            $this->error('Failed to import file: '.$throwable->getMessage());

            return;
        }

        $this->info('Import completed successfully!');

        $this->verifyImport($service, $storeName);
    }

    private function resolveFilePath(): string
    {
        $optionFilePath = $this->option('file-path');

        if (is_string($optionFilePath) && $optionFilePath !== '') {
            return $optionFilePath;
        }

        $defaultFilePath = config('gemini.default_upload_file_path', storage_path('sources/FoodData_Central_foundation_food_json_2025-04-24 3.json'));

        return is_string($defaultFilePath) ? $defaultFilePath : '';
    }

    private function verifyImport(CurriculumIndexService $service, string $storeName): void
    {
        $storeData = $service->getStoreStatus($storeName);

        if (! $storeData instanceof GeminiFileSearchStoreData) {
            return;
        }

        if ($storeData->activeDocumentsCount === 0 && $storeData->pendingDocumentsCount === 0) {
            $this->warn('⚠ Document count is still 0. This may take a few moments to update.');

            return;
        }

        $this->info(sprintf('✓ Verified: %d active, %d pending (%s MB)', $storeData->activeDocumentsCount, $storeData->pendingDocumentsCount, $storeData->getSizeMB()));
    }
}
