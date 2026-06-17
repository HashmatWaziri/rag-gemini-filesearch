<?php

declare(strict_types=1);

use App\Enums\Glc\GlcLevel;
use App\Enums\SettingKey;
use App\Models\Setting;
use App\Services\Glc\Admin\PlacementScoringSettings;

it('computes weighted composites and re-normalizes when sections are missing', function (): void {
    $settings = app(PlacementScoringSettings::class);

    expect($settings->compositeFromSectionScores([
        'reading' => 80.0,
        'grammar_vocabulary' => 60.0,
        'listening' => null,
        'writing' => null,
        'speaking' => null,
    ]))->toBe(70.0);

    Setting::set(SettingKey::GlcPlacementScoringSettings, json_encode([
        'section_weights' => [
            'reading' => 0.30,
            'grammar_vocabulary' => 0.30,
            'listening' => 0.20,
            'writing' => 0.10,
            'speaking' => 0.10,
        ],
    ]));

    expect($settings->compositeFromSectionScores([
        'reading' => 80.0,
        'grammar_vocabulary' => 60.0,
        'listening' => null,
        'writing' => null,
        'speaking' => null,
    ]))->toBe(70.0);
});

it('maps composites using configurable level band minimums', function (): void {
    $settings = app(PlacementScoringSettings::class);

    Setting::set(SettingKey::GlcPlacementScoringSettings, json_encode([
        'level_band_minimums' => [
            'beginner' => 10.0,
            'elementary' => 25.0,
            'pre_intermediate' => 40.0,
            'intermediate' => 55.0,
            'upper_intermediate' => 70.0,
            'advanced' => 85.0,
        ],
    ]));

    expect($settings->levelFromComposite(12.0))->toBe(GlcLevel::Beginner)
        ->and($settings->levelFromComposite(84.0))->toBe(GlcLevel::UpperIntermediate)
        ->and($settings->levelBandDescriptions()[GlcLevel::Starter->value])->toBe('0-10%');
});

it('rejects section weights that do not sum to one hundred percent', function (): void {
    $settings = app(PlacementScoringSettings::class);

    expect($settings->weightsSumToOne([
        'reading' => 0.30,
        'grammar_vocabulary' => 0.30,
        'listening' => 0.20,
        'writing' => 0.10,
        'speaking' => 0.05,
    ]))->toBeFalse();
});

it('rejects level bands that are not strictly increasing', function (): void {
    $settings = app(PlacementScoringSettings::class);

    expect($settings->levelBandsAreStrictlyIncreasing([
        'beginner' => 15.0,
        'elementary' => 30.0,
        'pre_intermediate' => 30.0,
        'intermediate' => 60.0,
        'upper_intermediate' => 75.0,
        'advanced' => 90.0,
    ]))->toBeFalse();
});
