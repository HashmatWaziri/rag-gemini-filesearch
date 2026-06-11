<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\SettingKey;
use App\Models\Setting;

final class TutorOperationalSettings
{
    public const int MIN_ROTATION_THRESHOLD = 10;

    public const int MAX_ROTATION_THRESHOLD = 200;

    public const int MIN_ROTATION_SUMMARIZE = 5;

    public const int MAX_ROTATION_SUMMARIZE = 100;

    public const int MIN_VIOLATION_THRESHOLD = 1;

    public const int MAX_VIOLATION_THRESHOLD = 20;

    public const int MIN_VIOLATION_WINDOW_DAYS = 1;

    public const int MAX_VIOLATION_WINDOW_DAYS = 30;

    /**
     * @return array{
     *     rotation_threshold_pairs: int,
     *     rotation_summarize_pairs: int,
     *     violation_notification_threshold: int,
     *     violation_notification_window_days: int,
     * }
     */
    public function defaults(): array
    {
        return [
            'rotation_threshold_pairs' => config()->integer('glc.tutor.rotation_threshold_pairs', 40),
            'rotation_summarize_pairs' => config()->integer('glc.tutor.rotation_summarize_pairs', 20),
            'violation_notification_threshold' => config()->integer('glc.tutor.violation_notification_threshold', 3),
            'violation_notification_window_days' => config()->integer('glc.tutor.violation_notification_window_days', 7),
        ];
    }

    /**
     * @return array{
     *     rotation_threshold_pairs: int,
     *     rotation_summarize_pairs: int,
     *     violation_notification_threshold: int,
     *     violation_notification_window_days: int,
     * }
     */
    public function effective(): array
    {
        $effective = $this->defaults();
        $stored = Setting::get(SettingKey::GlcTutorOperationalSettings);

        if (! is_string($stored) || $stored === '') {
            return $effective;
        }

        $overrides = json_decode($stored, true);

        if (! is_array($overrides)) {
            return $effective;
        }

        foreach (array_keys($effective) as $key) {
            if (is_numeric($overrides[$key] ?? null)) {
                $effective[$key] = (int) $overrides[$key];
            }
        }

        return $effective;
    }

    /**
     * @param  array<string, int>  $settings
     */
    public function update(array $settings): void
    {
        Setting::set(SettingKey::GlcTutorOperationalSettings, json_encode($settings));
    }
}
