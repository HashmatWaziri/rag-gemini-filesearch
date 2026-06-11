<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum PlacementAccessCodeStatus: string
{
    case Unused = 'unused';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Unused => 'Unused',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Revoked => 'Revoked',
        };
    }
}
