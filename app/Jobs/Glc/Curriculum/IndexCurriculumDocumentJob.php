<?php

declare(strict_types=1);

namespace App\Jobs\Glc\Curriculum;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Models\Glc\CurriculumDocument;
use App\Services\Glc\Curriculum\CurriculumIndexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use RuntimeException;

#[DeleteWhenMissingModels]
final class IndexCurriculumDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public CurriculumDocument $document) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(CurriculumIndexService $service): void
    {
        if ($this->document->status !== CurriculumDocumentStatus::Published) {
            return;
        }

        $service->index($this->document);

        if ($this->document->refresh()->index_status !== CurriculumIndexStatus::Failed || ! $service->isConfigured()) {
            return;
        }

        if ($this->attempts() < $this->tries) {
            $this->release($this->backoff()[$this->attempts() - 1] ?? 300);

            return;
        }

        report(new RuntimeException(sprintf(
            'Curriculum document [%d] "%s" could not be imported into the File Search store after %d attempts: %s',
            $this->document->id,
            $this->document->title,
            $this->attempts(),
            (string) $this->document->index_error,
        )));
    }
}
