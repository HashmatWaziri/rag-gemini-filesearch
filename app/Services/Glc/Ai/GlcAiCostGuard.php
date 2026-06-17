<?php

declare(strict_types=1);

namespace App\Services\Glc\Ai;

use App\Exceptions\Glc\GlcAiCostLimitExceededException;
use App\Models\AiUsage;
use App\Services\Glc\Admin\GlcAiCostSettings;
use Carbon\CarbonImmutable;

final readonly class GlcAiCostGuard
{
    public function __construct(
        private GlcAiCostSettings $settings,
    ) {}

    /**
     * @throws GlcAiCostLimitExceededException
     */
    public function assertWithinLimits(): void
    {
        $config = $this->settings->effective();

        if (! $config['enforcement_enabled']) {
            return;
        }

        $estimate = $this->preflightEstimateUsd();
        $now = CarbonImmutable::now();

        $windows = [
            'rolling' => [
                'limit' => $config['rolling_limit_usd'],
                'start' => $now->subHours($config['rolling_period_hours']),
                'period_hours' => $config['rolling_period_hours'],
                'period_days' => null,
            ],
            'weekly' => [
                'limit' => $config['weekly_limit_usd'],
                'start' => $now->subDays($config['weekly_period_days']),
                'period_hours' => null,
                'period_days' => $config['weekly_period_days'],
            ],
        ];

        foreach ($windows as $type => $window) {
            $current = $this->costForPeriod($window['start'], $now);

            if ($current + $estimate > $window['limit']) {
                throw new GlcAiCostLimitExceededException(
                    limitType: $type,
                    currentUsd: $current,
                    limitUsd: $window['limit'],
                    resetsAt: $this->resetsAt($now, $window['start'], $type, $window),
                );
            }
        }
    }

    public function preflightEstimateUsd(): float
    {
        return (float) config()->float('glc.ai_cost.preflight_estimate_usd', 0.01);
    }

    private function costForPeriod(CarbonImmutable $start, CarbonImmutable $end): float
    {
        return (float) AiUsage::query()
            ->glc()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->sum('cost');
    }

    /**
     * @param  array{limit: float, start: CarbonImmutable, period_hours: int|null, period_days: int|null}  $window
     */
    private function resetsAt(CarbonImmutable $now, CarbonImmutable $periodStart, string $type, array $window): CarbonImmutable
    {
        $oldest = AiUsage::query()
            ->glc()
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<=', $now)
            ->min('created_at');

        if (! is_string($oldest)) {
            return match ($type) {
                'rolling' => $now->addHours((int) $window['period_hours']),
                default => $now->addDays((int) $window['period_days']),
            };
        }

        $oldestAt = CarbonImmutable::parse($oldest);

        return match ($type) {
            'rolling' => $oldestAt->addHours((int) $window['period_hours']),
            default => $oldestAt->addDays((int) $window['period_days']),
        };
    }
}
