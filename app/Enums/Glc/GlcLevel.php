<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum GlcLevel: string
{
    case Starter = 'starter';
    case Beginner = 'beginner';
    case Elementary = 'elementary';
    case PreIntermediate = 'pre_intermediate';
    case Intermediate = 'intermediate';
    case UpperIntermediate = 'upper_intermediate';
    case Advanced = 'advanced';

    public static function fromComposite(float $percentage): self
    {
        return match (true) {
            $percentage < 15.0 => self::Starter,
            $percentage < 30.0 => self::Beginner,
            $percentage < 45.0 => self::Elementary,
            $percentage < 60.0 => self::PreIntermediate,
            $percentage < 75.0 => self::Intermediate,
            $percentage < 90.0 => self::UpperIntermediate,
            default => self::Advanced,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Starter => 'Starter',
            self::Beginner => 'Beginner',
            self::Elementary => 'Elementary',
            self::PreIntermediate => 'Pre-Intermediate',
            self::Intermediate => 'Intermediate',
            self::UpperIntermediate => 'Upper-Intermediate',
            self::Advanced => 'Advanced',
        };
    }

    public function ordinal(): int
    {
        return match ($this) {
            self::Starter => 1,
            self::Beginner => 2,
            self::Elementary => 3,
            self::PreIntermediate => 4,
            self::Intermediate => 5,
            self::UpperIntermediate => 6,
            self::Advanced => 7,
        };
    }
}
