<?php

declare(strict_types=1);

namespace App\Console\Commands\Glc\Curriculum;

use App\Services\Glc\Curriculum\GeminiFileSearchService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Re-import every Published curriculum document into the Gemini File Search store')]
#[Signature('rebuild:gemini-file-search-store')]
final class RebuildGeminiFileSearchStoreCommand extends Command
{
    public function handle(GeminiFileSearchService $service): int
    {
        if (! $service->isConfigured()) {
            $this->warn('Gemini API key is not configured. Nothing was rebuilt.');

            return self::SUCCESS;
        }

        $this->info('Rebuilding the File Search store from published curriculum documents...');

        $result = $service->rebuildStore();

        $this->table(
            ['Result', 'Documents'],
            [
                ['Published documents found', $result['total']],
                ['Re-imported successfully', $result['succeeded']],
                ['Failed', $result['failed']],
            ],
        );

        if ($result['failed'] > 0) {
            $this->error(sprintf(
                '%d document(s) could not be re-imported. Check the application log and each document\'s status, then run this command again.',
                $result['failed'],
            ));

            return self::FAILURE;
        }

        $this->info('File Search store rebuild completed.');

        return self::SUCCESS;
    }
}
