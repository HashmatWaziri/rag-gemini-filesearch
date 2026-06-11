<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum PlacementAttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Progress',
            self::Submitted => 'Submitted',
            self::Terminated => 'Terminated',
        };
    }
}
