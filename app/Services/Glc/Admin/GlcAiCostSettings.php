<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\SettingKey;
use App\Models\Setting;

final class GlcAiCostSettings
{
    public const float MIN_LIMIT_USD = 1.0;

    public const float MAX_LIMIT_USD = 10_000.0;

    /**
     * @return array{
     *     enforcement_enabled: bool,
     *     rolling_limit_usd: float,
     *     rolling_period_hours: int,
     *     weekly_limit_usd: float,
     *     weekly_period_days: int,
     * }
     */
    public function defaults(): array
    {
        /** @var array{enforcement_enabled?: bool, rolling?: array{limit_usd?: float, period_hours?: int}, weekly?: array{limit_usd?: float, period_days?: int}} $config */
        $config = config()->array('glc.ai_cost', []);

        return [
            'enforcement_enabled' => (bool) ($config['enforcement_enabled'] ?? true),
            'rolling_limit_usd' => (float) ($config['rolling']['limit_usd'] ?? 25.0),
            'rolling_period_hours' => (int) ($config['rolling']['period_hours'] ?? 24),
            'weekly_limit_usd' => (float) ($config['weekly']['limit_usd'] ?? 100.0),
            'weekly_period_days' => (int) ($config['weekly']['period_days'] ?? 7),
        ];
    }

    /**
     * @return array{
     *     enforcement_enabled: bool,
     *     rolling_limit_usd: float,
     *     rolling_period_hours: int,
     *     weekly_limit_usd: float,
     *     weekly_period_days: int,
     * }
     */
    public function effective(): array
    {
        $effective = $this->defaults();
        $stored = Setting::get(SettingKey::GlcAiCostSettings);

        if (! is_string($stored) || $stored === '') {
            return $effective;
        }

        $overrides = json_decode($stored, true);

        if (! is_array($overrides)) {
            return $effective;
        }

        if (array_key_exists('enforcement_enabled', $overrides)) {
            $effective['enforcement_enabled'] = filter_var(
                $overrides['enforcement_enabled'],
                FILTER_VALIDATE_BOOL,
            );
        }

        foreach (['rolling_limit_usd', 'weekly_limit_usd'] as $key) {
            if (is_numeric($overrides[$key] ?? null)) {
                $effective[$key] = round((float) $overrides[$key], 2);
            }
        }

        foreach (['rolling_period_hours', 'weekly_period_days'] as $key) {
            if (is_numeric($overrides[$key] ?? null)) {
                $effective[$key] = (int) $overrides[$key];
            }
        }

        return $effective;
    }

    /**
     * @param  array<string, bool|float|int>  $settings
     */
    public function update(array $settings): void
    {
        Setting::set(SettingKey::GlcAiCostSettings, json_encode($settings));
    }
}
