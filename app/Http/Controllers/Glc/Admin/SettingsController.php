<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\PlacementSection;
use App\Services\Glc\Admin\PlacementScoringSettings;
use App\Services\Glc\Admin\SectionTimeLimits;
use App\Services\Glc\Admin\TutorMaterialsHealth;
use App\Services\Glc\Admin\TutorOperationalSettings;
use App\Services\Glc\AuditLogger;
use App\Services\Glc\Curriculum\GeminiFileSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SettingsController
{
    public function __construct(
        private SectionTimeLimits $timeLimits,
        private PlacementScoringSettings $placementScoring,
        private TutorMaterialsHealth $tutorMaterialsHealth,
        private TutorOperationalSettings $tutorOperationalSettings,
        private AuditLogger $auditLogger,
    ) {}

    public function edit(Request $request): Response
    {
        return Inertia::render('glc/admin/settings/edit', [
            'sections' => array_map(fn (PlacementSection $section): array => [
                'value' => $section->value,
                'label' => $section->label(),
            ], PlacementSection::ordered()),
            'defaults' => $this->timeLimits->defaults(),
            'effective' => $this->timeLimits->effective(),
            'bounds' => [
                'min' => SectionTimeLimits::MIN_SECONDS,
                'max' => SectionTimeLimits::MAX_SECONDS,
            ],
            'tutorOperational' => [
                'defaults' => $this->tutorOperationalSettings->defaults(),
                'effective' => $this->tutorOperationalSettings->effective(),
                'bounds' => [
                    'rotation_threshold_pairs' => [
                        'min' => TutorOperationalSettings::MIN_ROTATION_THRESHOLD,
                        'max' => TutorOperationalSettings::MAX_ROTATION_THRESHOLD,
                    ],
                    'rotation_summarize_pairs' => [
                        'min' => TutorOperationalSettings::MIN_ROTATION_SUMMARIZE,
                        'max' => TutorOperationalSettings::MAX_ROTATION_SUMMARIZE,
                    ],
                    'violation_notification_threshold' => [
                        'min' => TutorOperationalSettings::MIN_VIOLATION_THRESHOLD,
                        'max' => TutorOperationalSettings::MAX_VIOLATION_THRESHOLD,
                    ],
                    'violation_notification_window_days' => [
                        'min' => TutorOperationalSettings::MIN_VIOLATION_WINDOW_DAYS,
                        'max' => TutorOperationalSettings::MAX_VIOLATION_WINDOW_DAYS,
                    ],
                ],
            ],
            'tutorMaterials' => [
                'counts' => $this->tutorMaterialsHealth->counts(),
                'rebuild_available' => class_exists(GeminiFileSearchService::class),
            ],
            'placementScoring' => [
                'defaults' => $this->placementScoring->defaults(),
                'effective' => $this->placementScoring->effective(),
                'bounds' => [
                    'section_weight' => [
                        'min' => PlacementScoringSettings::MIN_SECTION_WEIGHT,
                        'max' => PlacementScoringSettings::MAX_SECTION_WEIGHT,
                    ],
                    'level_band' => [
                        'min' => PlacementScoringSettings::MIN_LEVEL_BAND,
                        'max' => PlacementScoringSettings::MAX_LEVEL_BAND,
                    ],
                    'variance_flag_threshold' => [
                        'min' => PlacementScoringSettings::MIN_VARIANCE_THRESHOLD,
                        'max' => PlacementScoringSettings::MAX_VARIANCE_THRESHOLD,
                    ],
                ],
                'level_keys' => PlacementScoringSettings::configurableLevelKeys(),
            ],
            'status' => $request->session()->get('glc_status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];

        foreach (PlacementSection::ordered() as $section) {
            $rules['section_time_limits.'.$section->value] = [
                'required',
                'integer',
                'between:'.SectionTimeLimits::MIN_SECONDS.','.SectionTimeLimits::MAX_SECONDS,
            ];
        }

        $rules['tutor_operational.rotation_threshold_pairs'] = [
            'required',
            'integer',
            'between:'.TutorOperationalSettings::MIN_ROTATION_THRESHOLD.','.TutorOperationalSettings::MAX_ROTATION_THRESHOLD,
        ];
        $rules['tutor_operational.rotation_summarize_pairs'] = [
            'required',
            'integer',
            'between:'.TutorOperationalSettings::MIN_ROTATION_SUMMARIZE.','.TutorOperationalSettings::MAX_ROTATION_SUMMARIZE,
        ];
        $rules['tutor_operational.violation_notification_threshold'] = [
            'required',
            'integer',
            'between:'.TutorOperationalSettings::MIN_VIOLATION_THRESHOLD.','.TutorOperationalSettings::MAX_VIOLATION_THRESHOLD,
        ];
        $rules['tutor_operational.violation_notification_window_days'] = [
            'required',
            'integer',
            'between:'.TutorOperationalSettings::MIN_VIOLATION_WINDOW_DAYS.','.TutorOperationalSettings::MAX_VIOLATION_WINDOW_DAYS,
        ];

        foreach (PlacementSection::ordered() as $section) {
            $rules['placement_scoring.section_weights.'.$section->value] = [
                'required',
                'numeric',
                'between:'.PlacementScoringSettings::MIN_SECTION_WEIGHT.','.PlacementScoringSettings::MAX_SECTION_WEIGHT,
            ];
        }

        foreach (PlacementScoringSettings::configurableLevelKeys() as $level) {
            $rules['placement_scoring.level_band_minimums.'.$level] = [
                'required',
                'numeric',
                'between:'.PlacementScoringSettings::MIN_LEVEL_BAND.','.PlacementScoringSettings::MAX_LEVEL_BAND,
            ];
        }

        $rules['placement_scoring.variance_flag_threshold'] = [
            'required',
            'numeric',
            'between:'.PlacementScoringSettings::MIN_VARIANCE_THRESHOLD.','.PlacementScoringSettings::MAX_VARIANCE_THRESHOLD,
        ];

        $validated = $request->validate($rules);

        $limits = array_map(intval(...), $validated['section_time_limits']);

        $this->timeLimits->update($limits);

        $tutorOperational = array_map(intval(...), $validated['tutor_operational']);

        $sectionWeights = array_map(
            fn (mixed $weight): float => round((float) $weight, 4),
            $validated['placement_scoring']['section_weights'],
        );

        if (! $this->placementScoring->weightsSumToOne($sectionWeights)) {
            throw ValidationException::withMessages([
                'placement_scoring.section_weights' => 'Section weights must add up to 100%.',
            ]);
        }

        $levelBandMinimums = array_map(
            fn (mixed $minimum): float => round((float) $minimum, 2),
            $validated['placement_scoring']['level_band_minimums'],
        );

        if (! $this->placementScoring->levelBandsAreStrictlyIncreasing($levelBandMinimums)) {
            throw ValidationException::withMessages([
                'placement_scoring.level_band_minimums' => 'Level band minimums must increase from Beginner through Advanced.',
            ]);
        }

        $placementScoring = [
            'section_weights' => $sectionWeights,
            'level_band_minimums' => $levelBandMinimums,
            'variance_flag_threshold' => round((float) $validated['placement_scoring']['variance_flag_threshold'], 2),
        ];

        $this->tutorOperationalSettings->update($tutorOperational);

        $this->placementScoring->update($placementScoring);

        $this->auditLogger->log(AuditAction::SettingsUpdated, $request->user(), null, [
            'section_time_limits' => $limits,
            'tutor_operational' => $tutorOperational,
            'placement_scoring' => $placementScoring,
        ]);

        return to_route('admin.settings.edit')->with('glc_status', 'Settings saved.');
    }
}
