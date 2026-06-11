<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum PlacementSectionStatus: string
{
    case Locked = 'locked';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Locked => 'Locked',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
        };
    }
}
