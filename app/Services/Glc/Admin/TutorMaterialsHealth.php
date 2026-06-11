<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Models\Glc\CurriculumDocument;

final class TutorMaterialsHealth
{
    /**
     * @return array{draft: int, publishing: int, published: int, publish_failed: int, archived: int}
     */
    public function counts(): array
    {
        $counts = [
            'draft' => 0,
            'publishing' => 0,
            'published' => 0,
            'publish_failed' => 0,
            'archived' => 0,
        ];

        $rows = CurriculumDocument::query()
            ->toBase()
            ->select(['status', 'index_status'])
            ->selectRaw('count(*) as total')
            ->groupBy('status', 'index_status')
            ->get();

        foreach ($rows as $row) {
            $bucket = $this->bucket(
                CurriculumDocumentStatus::from((string) $row->status),
                CurriculumIndexStatus::from((string) $row->index_status),
            );

            $counts[$bucket] += (int) $row->total;
        }

        return $counts;
    }

    private function bucket(CurriculumDocumentStatus $status, CurriculumIndexStatus $indexStatus): string
    {
        return match ($status) {
            CurriculumDocumentStatus::Draft => 'draft',
            CurriculumDocumentStatus::Publishing => 'publishing',
            CurriculumDocumentStatus::Published => 'published',
            CurriculumDocumentStatus::PublishFailed => 'publish_failed',
            CurriculumDocumentStatus::Archived => 'archived',
        };
    }
}
