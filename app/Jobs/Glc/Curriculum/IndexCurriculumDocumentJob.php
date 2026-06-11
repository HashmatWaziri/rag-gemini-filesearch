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
        if (! $this->shouldIndex($this->document)) {
            return;
        }

        $wasLive = $this->document->status === CurriculumDocumentStatus::Published;

        $service->index($this->document);

        $document = $this->document->refresh();

        if ($document->index_status === CurriculumIndexStatus::Indexed) {
            if (! $wasLive) {
                $document->update([
                    'status' => CurriculumDocumentStatus::Published,
                    'published_at' => $document->published_at ?? now(),
                ]);
            }

            return;
        }

        if ($document->index_status !== CurriculumIndexStatus::Failed) {
            return;
        }

        if (! $wasLive && (! $service->isConfigured() || $this->attempts() >= $this->tries)) {
            $document->update(['status' => CurriculumDocumentStatus::PublishFailed]);

            if ($service->isConfigured()) {
                report(new RuntimeException(sprintf(
                    'Curriculum document [%d] "%s" could not be imported into the File Search store after %d attempts: %s',
                    $document->id,
                    $document->title,
                    $this->attempts(),
                    (string) $document->index_error,
                )));
            }

            return;
        }

        if ($this->attempts() < $this->tries) {
            $this->release($this->backoff()[$this->attempts() - 1] ?? 300);

            return;
        }

        if (! $wasLive) {
            $document->update(['status' => CurriculumDocumentStatus::PublishFailed]);
        }

        report(new RuntimeException(sprintf(
            'Curriculum document [%d] "%s" could not be imported into the File Search store after %d attempts: %s',
            $document->id,
            $document->title,
            $this->attempts(),
            (string) $document->index_error,
        )));
    }

    private function shouldIndex(CurriculumDocument $document): bool
    {
        return match ($document->status) {
            CurriculumDocumentStatus::Publishing,
            CurriculumDocumentStatus::PublishFailed => true,
            CurriculumDocumentStatus::Published => in_array(
                $document->index_status,
                [CurriculumIndexStatus::Pending, CurriculumIndexStatus::Failed],
                true,
            ),
            default => false,
        };
    }
}
