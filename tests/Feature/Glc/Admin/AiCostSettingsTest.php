<?php

declare(strict_types=1);

use App\Exceptions\Glc\GlcAiCostLimitExceededException;
use App\Models\AiUsage;
use App\Models\User;
use App\Services\Glc\Admin\GlcAiCostSettings;
use App\Services\Glc\Ai\GlcAiCostGuard;
use App\Services\Glc\Tutor\GlcTutorAgent;

covers(GlcAiCostGuard::class);

beforeEach(function (): void {
    $this->withoutVite();
    config([
        'glc.ai_cost.enforcement_enabled' => true,
        'glc.ai_cost.rolling.limit_usd' => 0.50,
        'glc.ai_cost.weekly.limit_usd' => 2.00,
        'glc.ai_cost.preflight_estimate_usd' => 0.01,
    ]);
});

function costGuard(): GlcAiCostGuard
{
    return resolve(GlcAiCostGuard::class);
}

it('allows GLC AI calls under the configured platform limits', function (): void {
    AiUsage::factory()->create([
        'agent' => GlcTutorAgent::class,
        'cost' => 0.10,
    ]);

    costGuard()->assertWithinLimits();

    expect(true)->toBeTrue();
});

it('blocks GLC AI calls when the rolling platform limit would be exceeded', function (): void {
    AiUsage::factory()->create([
        'agent' => GlcTutorAgent::class,
        'cost' => 0.50,
    ]);

    costGuard()->assertWithinLimits();
})
    ->throws(GlcAiCostLimitExceededException::class);

it('does not enforce limits when disabled in settings', function (): void {
    resolve(GlcAiCostSettings::class)->update(['enforcement_enabled' => false]);

    AiUsage::factory()->create([
        'agent' => GlcTutorAgent::class,
        'cost' => 5.0,
    ]);

    costGuard()->assertWithinLimits();

    expect(true)->toBeTrue();
});

it('ignores non-GLC agent usage when calculating platform spend', function (): void {
    AiUsage::factory()->create([
        'agent' => 'App\\Ai\\Agents\\AgentRunner',
        'cost' => 5.0,
    ]);

    costGuard()->assertWithinLimits();

    expect(true)->toBeTrue();
});

it('renders the AI cost controls page for admins', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings.ai-cost.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/admin/settings/ai-cost')
            ->has('usage.rolling')
            ->has('settings.effective'));
});

it('updates AI cost limits from the admin UI', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.ai-cost.update'), [
            'enforcement_enabled' => true,
            'rolling_limit_usd' => 40,
            'rolling_period_hours' => 24,
            'weekly_limit_usd' => 150,
            'weekly_period_days' => 7,
        ])
        ->assertRedirect(route('admin.settings.ai-cost.edit'));

    expect(resolve(GlcAiCostSettings::class)->effective())
        ->rolling_limit_usd->toBe(40.0)
        ->weekly_limit_usd->toBe(150.0);
});
