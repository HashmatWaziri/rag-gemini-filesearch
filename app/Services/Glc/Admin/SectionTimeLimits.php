<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\Glc\PlacementSection;
use App\Enums\SettingKey;
use App\Models\Setting;

final class SectionTimeLimits
{
    public const int MIN_SECONDS = 60;

    public const int MAX_SECONDS = 7200;

    /**
     * @return array<string, int>
     */
    public function defaults(): array
    {
        $defaults = [];

        foreach (PlacementSection::ordered() as $section) {
            $defaults[$section->value] = config()->integer('glc.placement.section_time_limits.'.$section->value, 900);
        }

        return $defaults;
    }

    /**
     * @return array<string, int>
     */
    public function effective(): array
    {
        $effective = $this->defaults();
        $stored = Setting::get(SettingKey::GlcSectionTimeLimits);

        if (! is_string($stored) || $stored === '') {
            return $effective;
        }

        $overrides = json_decode($stored, true);

        if (! is_array($overrides)) {
            return $effective;
        }

        foreach (array_keys($effective) as $section) {
            if (is_numeric($overrides[$section] ?? null)) {
                $effective[$section] = (int) $overrides[$section];
            }
        }

        return $effective;
    }

    /**
     * @param  array<string, int>  $limits
     */
    public function update(array $limits): void
    {
        Setting::set(SettingKey::GlcSectionTimeLimits, json_encode($limits));
    }
}
