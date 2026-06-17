<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementSection;
use App\Enums\SettingKey;
use App\Models\Setting;

final class PlacementScoringSettings
{
    public const float MIN_SECTION_WEIGHT = 0.05;

    public const float MAX_SECTION_WEIGHT = 0.60;

    public const float WEIGHT_SUM_TOLERANCE = 0.001;

    public const float MIN_LEVEL_BAND = 1.0;

    public const float MAX_LEVEL_BAND = 99.0;

    public const float MIN_VARIANCE_THRESHOLD = 5.0;

    public const float MAX_VARIANCE_THRESHOLD = 100.0;

    /**
     * @return list<string>
     */
    public static function configurableLevelKeys(): array
    {
        return [
            GlcLevel::Beginner->value,
            GlcLevel::Elementary->value,
            GlcLevel::PreIntermediate->value,
            GlcLevel::Intermediate->value,
            GlcLevel::UpperIntermediate->value,
            GlcLevel::Advanced->value,
        ];
    }

    /**
     * @return array<string, float>
     */
    public function defaultSectionWeights(): array
    {
        $weights = [];

        foreach (PlacementSection::ordered() as $section) {
            $weights[$section->value] = (float) config(
                'glc.placement.section_weights.'.$section->value,
                0.20,
            );
        }

        return $weights;
    }

    /**
     * @return array<string, float>
     */
    public function defaultLevelBandMinimums(): array
    {
        $minimums = [];

        foreach (self::configurableLevelKeys() as $level) {
            $minimums[$level] = (float) config(
                'glc.placement.level_band_minimums.'.$level,
                GlcLevel::defaultBandMinimums()[$level],
            );
        }

        return $minimums;
    }

    public function defaultVarianceFlagThreshold(): float
    {
        return (float) config('glc.placement.variance_flag_threshold', 30.0);
    }

    /**
     * @return array{
     *     section_weights: array<string, float>,
     *     level_band_minimums: array<string, float>,
     *     variance_flag_threshold: float,
     * }
     */
    public function defaults(): array
    {
        return [
            'section_weights' => $this->defaultSectionWeights(),
            'level_band_minimums' => $this->defaultLevelBandMinimums(),
            'variance_flag_threshold' => $this->defaultVarianceFlagThreshold(),
        ];
    }

    /**
     * @return array{
     *     section_weights: array<string, float>,
     *     level_band_minimums: array<string, float>,
     *     variance_flag_threshold: float,
     * }
     */
    public function effective(): array
    {
        $effective = $this->defaults();
        $stored = Setting::get(SettingKey::GlcPlacementScoringSettings);

        if (! is_string($stored) || $stored === '') {
            return $effective;
        }

        $overrides = json_decode($stored, true);

        if (! is_array($overrides)) {
            return $effective;
        }

        if (is_array($overrides['section_weights'] ?? null)) {
            foreach (array_keys($effective['section_weights']) as $section) {
                if (is_numeric($overrides['section_weights'][$section] ?? null)) {
                    $effective['section_weights'][$section] = (float) $overrides['section_weights'][$section];
                }
            }
        }

        if (is_array($overrides['level_band_minimums'] ?? null)) {
            foreach (self::configurableLevelKeys() as $level) {
                if (is_numeric($overrides['level_band_minimums'][$level] ?? null)) {
                    $effective['level_band_minimums'][$level] = (float) $overrides['level_band_minimums'][$level];
                }
            }
        }

        if (is_numeric($overrides['variance_flag_threshold'] ?? null)) {
            $effective['variance_flag_threshold'] = (float) $overrides['variance_flag_threshold'];
        }

        return $effective;
    }

    /**
     * @param  array{
     *     section_weights: array<string, float>,
     *     level_band_minimums: array<string, float>,
     *     variance_flag_threshold: float,
     * }  $settings
     */
    public function update(array $settings): void
    {
        Setting::set(SettingKey::GlcPlacementScoringSettings, json_encode($settings));
    }

    public function varianceFlagThreshold(): float
    {
        return $this->effective()['variance_flag_threshold'];
    }

    public function levelFromComposite(float $percentage): GlcLevel
    {
        return GlcLevel::fromComposite($percentage, $this->effective()['level_band_minimums']);
    }

    /**
     * @param  array<string, float|null>  $sectionScores
     */
    public function compositeFromSectionScores(array $sectionScores): ?float
    {
        $weights = $this->effective()['section_weights'];
        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach (PlacementSection::ordered() as $section) {
            $score = $sectionScores[$section->value] ?? null;

            if (! is_numeric($score)) {
                continue;
            }

            $weight = $weights[$section->value];
            $weightedSum += $weight * (float) $score;
            $weightTotal += $weight;
        }

        if ($weightTotal <= 0.0) {
            return null;
        }

        return round($weightedSum / $weightTotal, 2);
    }

    public function levelGuide(): string
    {
        $lines = [];

        foreach ($this->levelBandDescriptions() as $level => $range) {
            $glcLevel = GlcLevel::from($level);
            $lines[] = sprintf('- %s (%s): approx. %s', $glcLevel->label(), $level, $range);
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, string>
     */
    public function levelBandDescriptions(): array
    {
        $minimums = $this->effective()['level_band_minimums'];
        $descriptions = [];
        $levels = GlcLevel::cases();

        foreach ($levels as $index => $level) {
            if ($level === GlcLevel::Starter) {
                $upper = $minimums[GlcLevel::Beginner->value] - 0.01;
                $descriptions[$level->value] = sprintf('%.0f-%.0f%%', 0.0, max(0.0, $upper));

                continue;
            }

            $lower = $minimums[$level->value];
            $nextLevel = $levels[$index + 1] ?? null;
            $upper = $nextLevel === null
                ? 100.0
                : $minimums[$nextLevel->value] - 0.01;

            $descriptions[$level->value] = sprintf('%.0f-%.0f%%', $lower, $upper);
        }

        return $descriptions;
    }

    /**
     * @param  array<string, float>  $weights
     */
    public function weightsSumToOne(array $weights): bool
    {
        return abs(array_sum($weights) - 1.0) <= self::WEIGHT_SUM_TOLERANCE;
    }

    /**
     * @param  array<string, float>  $minimums
     */
    public function levelBandsAreStrictlyIncreasing(array $minimums): bool
    {
        $previous = 0.0;

        foreach (self::configurableLevelKeys() as $level) {
            $value = $minimums[$level] ?? null;

            if (! is_numeric($value) || (float) $value <= $previous) {
                return false;
            }

            $previous = (float) $value;
        }

        return true;
    }
}
