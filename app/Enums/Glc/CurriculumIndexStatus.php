<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum CurriculumIndexStatus: string
{
    case Pending = 'pending';
    case Indexing = 'indexing';
    case Indexed = 'indexed';
    case Failed = 'failed';
    case Removed = 'removed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Indexing => 'Indexing',
            self::Indexed => 'Indexed',
            self::Failed => 'Failed',
            self::Removed => 'Removed',
        };
    }
}
