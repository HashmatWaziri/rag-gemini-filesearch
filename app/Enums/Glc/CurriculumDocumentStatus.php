<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum CurriculumDocumentStatus: string
{
    case Draft = 'draft';
    case Publishing = 'publishing';
    case Published = 'published';
    case PublishFailed = 'publish_failed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Publishing => 'Publishing',
            self::Published => 'Published',
            self::PublishFailed => 'Publish failed',
            self::Archived => 'Archived',
        };
    }
}
