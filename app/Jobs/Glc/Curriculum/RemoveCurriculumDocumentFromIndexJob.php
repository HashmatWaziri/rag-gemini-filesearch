<?php

declare(strict_types=1);

namespace App\Jobs\Glc\Curriculum;

use App\Models\Glc\CurriculumDocument;
use App\Services\Glc\Curriculum\CurriculumIndexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

#[DeleteWhenMissingModels]
final class RemoveCurriculumDocumentFromIndexJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string|null  $documentName  Stale store document to delete after
     */
    public function __construct(
        public CurriculumDocument $document,
        public ?string $documentName = null,
    ) {}

    public function handle(CurriculumIndexService $service): void
    {
        $service->removeFromIndex($this->document, $this->documentName);
    }
}
