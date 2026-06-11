<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\PlacementSection;
use App\Services\Glc\Admin\SectionTimeLimits;
use App\Services\Glc\Admin\TutorMaterialsHealth;
use App\Services\Glc\AuditLogger;
use App\Services\Glc\Curriculum\GeminiFileSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SettingsController
{
    public function __construct(
        private SectionTimeLimits $timeLimits,
        private TutorMaterialsHealth $tutorMaterialsHealth,
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
            'tutorMaterials' => [
                'counts' => $this->tutorMaterialsHealth->counts(),
                'rebuild_available' => class_exists(GeminiFileSearchService::class),
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

        $validated = $request->validate($rules);

        $limits = array_map(intval(...), $validated['section_time_limits']);

        $this->timeLimits->update($limits);

        $this->auditLogger->log(AuditAction::SettingsUpdated, $request->user(), null, [
            'section_time_limits' => $limits,
        ]);

        return to_route('admin.settings.edit')->with('glc_status', 'Settings saved.');
    }
}
