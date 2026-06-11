<?php

declare(strict_types=1);

namespace App\Services\Glc\Curriculum;

use App\Enums\Glc\CurriculumIndexStatus;
use App\Models\Glc\CurriculumDocument;
use RuntimeException;
use Throwable;

final readonly class GeminiFileSearchService
{
    public function __construct(private CurriculumIndexService $index) {}

    public function isConfigured(): bool
    {
        return $this->index->isConfigured();
    }

    /**
     * @return array{total: int, succeeded: int, failed: int}
     */
    public function rebuildStore(): array
    {
        if (! $this->isConfigured()) {
            return ['total' => 0, 'succeeded' => 0, 'failed' => 0];
        }

        $documents = CurriculumDocument::query()->tutorRetrievable()->orderBy('id')->get();
        $total = $documents->count();

        try {
            $this->index->ensureStore();
        } catch (Throwable $throwable) {
            report($throwable);

            return ['total' => $total, 'succeeded' => 0, 'failed' => $total];
        }

        $succeeded = 0;
        $failed = 0;

        foreach ($documents as $document) {
            try {
                $this->index->index($document);
                $document->refresh();

                if ($document->index_status === CurriculumIndexStatus::Indexed) {
                    $succeeded++;

                    continue;
                }

                $failed++;
                report(new RuntimeException(sprintf(
                    'File Search rebuild failed for curriculum document [%d] "%s": %s',
                    $document->id,
                    $document->title,
                    (string) $document->index_error,
                )));
            } catch (Throwable $throwable) {
                $failed++;
                report($throwable);
            }
        }

        return ['total' => $total, 'succeeded' => $succeeded, 'failed' => $failed];
    }
}
