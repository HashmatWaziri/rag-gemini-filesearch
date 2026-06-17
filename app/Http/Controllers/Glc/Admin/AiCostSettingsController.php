<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Actions\Glc\GetGlcAiUsageDashboardAction;
use App\Enums\Glc\AuditAction;
use App\Services\Glc\Admin\GlcAiCostSettings;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AiCostSettingsController
{
    public function __construct(
        private GlcAiCostSettings $costSettings,
        private GetGlcAiUsageDashboardAction $usageDashboard,
        private AuditLogger $auditLogger,
    ) {}

    public function edit(Request $request): Response
    {
        $defaults = $this->costSettings->defaults();
        $effective = $this->costSettings->effective();

        return Inertia::render('glc/admin/settings/ai-cost', [
            'usage' => $this->usageDashboard->handle(),
            'settings' => [
                'defaults' => $defaults,
                'effective' => $effective,
                'bounds' => [
                    'limit_usd' => [
                        'min' => GlcAiCostSettings::MIN_LIMIT_USD,
                        'max' => GlcAiCostSettings::MAX_LIMIT_USD,
                    ],
                    'rolling_period_hours' => ['min' => 1, 'max' => 168],
                    'weekly_period_days' => ['min' => 1, 'max' => 30],
                ],
            ],
            'status' => $request->session()->get('glc_status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enforcement_enabled' => ['required', 'boolean'],
            'rolling_limit_usd' => [
                'required',
                'numeric',
                'between:'.GlcAiCostSettings::MIN_LIMIT_USD.','.GlcAiCostSettings::MAX_LIMIT_USD,
            ],
            'rolling_period_hours' => ['required', 'integer', 'between:1,168'],
            'weekly_limit_usd' => [
                'required',
                'numeric',
                'between:'.GlcAiCostSettings::MIN_LIMIT_USD.','.GlcAiCostSettings::MAX_LIMIT_USD,
            ],
            'weekly_period_days' => ['required', 'integer', 'between:1,30'],
        ]);

        $payload = [
            'enforcement_enabled' => (bool) $validated['enforcement_enabled'],
            'rolling_limit_usd' => round((float) $validated['rolling_limit_usd'], 2),
            'rolling_period_hours' => (int) $validated['rolling_period_hours'],
            'weekly_limit_usd' => round((float) $validated['weekly_limit_usd'], 2),
            'weekly_period_days' => (int) $validated['weekly_period_days'],
        ];

        $this->costSettings->update($payload);

        $this->auditLogger->log(AuditAction::SettingsUpdated, $request->user(), null, [
            'glc_ai_cost_settings' => $payload,
        ]);

        return to_route('admin.settings.ai-cost.edit')->with('glc_status', 'AI cost limits saved.');
    }
}
