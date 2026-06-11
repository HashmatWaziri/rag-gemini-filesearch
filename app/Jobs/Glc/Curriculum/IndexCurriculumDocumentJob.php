<?php

declare(strict_types=1);

namespace App\Jobs\Glc\Curriculum;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Models\Glc\CurriculumDocument;
use App\Services\Glc\Curriculum\CurriculumIndexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

#[DeleteWhenMissingModels]
final class IndexCurriculumDocumentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public CurriculumDocument $document) {}

    public function handle(CurriculumIndexService $service): void
    {
        if ($this->document->status !== CurriculumDocumentStatus::Published) {
            return;
        }

        $service->index($this->document);
    }
}
