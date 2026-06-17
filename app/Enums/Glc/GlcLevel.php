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

    /**
     * @param  array<string, float>|null  $bandMinimums
     */
    public static function fromComposite(float $percentage, ?array $bandMinimums = null): self
    {
        $minimums = $bandMinimums ?? self::defaultBandMinimums();

        $ordered = [
            self::Advanced,
            self::UpperIntermediate,
            self::Intermediate,
            self::PreIntermediate,
            self::Elementary,
            self::Beginner,
        ];

        foreach ($ordered as $level) {
            if ($percentage >= ($minimums[$level->value] ?? self::fallbackMinimum($level))) {
                return $level;
            }
        }

        return self::Starter;
    }

    /**
     * @return array<string, float>
     */
    public static function defaultBandMinimums(): array
    {
        $minimums = [];

        foreach ([
            self::Beginner,
            self::Elementary,
            self::PreIntermediate,
            self::Intermediate,
            self::UpperIntermediate,
            self::Advanced,
        ] as $level) {
            $minimums[$level->value] = (float) config(
                'glc.placement.level_band_minimums.'.$level->value,
                self::fallbackMinimum($level),
            );
        }

        return $minimums;
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

    private static function fallbackMinimum(self $level): float
    {
        return match ($level) {
            self::Beginner => 15.0,
            self::Elementary => 30.0,
            self::PreIntermediate => 45.0,
            self::Intermediate => 60.0,
            self::UpperIntermediate => 75.0,
            self::Advanced => 90.0,
            self::Starter => 0.0,
        };
    }
}
