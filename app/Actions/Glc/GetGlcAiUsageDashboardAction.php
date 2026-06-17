<?php

declare(strict_types=1);

namespace App\Actions\Glc;

use App\Models\AiUsage;
use App\Services\Glc\Admin\GlcAiCostSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class GetGlcAiUsageDashboardAction
{
    public function __construct(
        private GlcAiCostSettings $settings,
    ) {}

    /**
     * @return array{
     *     enforcement_enabled: bool,
     *     credit_multiplier: int,
     *     rolling: array{current: int, limit: int, percentage: int, resets_in: string, over_limit: bool, current_usd: float, limit_usd: float},
     *     weekly: array{current: int, limit: int, percentage: int, resets_in: string, over_limit: bool, current_usd: float, limit_usd: float},
     *     by_agent: list<array{agent: string, label: string, cost_usd: float, requests: int}>,
     *     recent_days: list<array{date: string, cost_usd: float, requests: int}>,
     * }
     */
    public function handle(): array
    {
        $config = $this->settings->effective();
        $multiplier = config()->integer('glc.ai_cost.credit_multiplier', 1_000);
        $now = CarbonImmutable::now();

        $rollingStart = $now->subHours($config['rolling_period_hours']);
        $weeklyStart = $now->subDays($config['weekly_period_days']);

        $rollingCost = $this->costForPeriod($rollingStart, $now);
        $weeklyCost = $this->costForPeriod($weeklyStart, $now);

        return [
            'enforcement_enabled' => $config['enforcement_enabled'],
            'credit_multiplier' => $multiplier,
            'rolling' => $this->buildBucket(
                $rollingCost,
                $config['rolling_limit_usd'],
                $multiplier,
                $this->resetsAt($now, $rollingStart, $config['rolling_period_hours'], null),
            ),
            'weekly' => $this->buildBucket(
                $weeklyCost,
                $config['weekly_limit_usd'],
                $multiplier,
                $this->resetsAt($now, $weeklyStart, null, $config['weekly_period_days']),
            ),
            'by_agent' => $this->usageByAgent($weeklyStart, $now),
            'recent_days' => $this->recentDailyTotals($now->subDays(6), $now),
        ];
    }

    /**
     * @return array{current: int, limit: int, percentage: int, resets_in: string, over_limit: bool, current_usd: float, limit_usd: float}
     */
    private function buildBucket(float $cost, float $limit, int $multiplier, CarbonImmutable $resetTime): array
    {
        return [
            'current' => (int) round($cost * $multiplier),
            'limit' => (int) round($limit * $multiplier),
            'current_usd' => round($cost, 4),
            'limit_usd' => round($limit, 2),
            'percentage' => $limit > 0 ? (int) min(100, round(($cost / $limit) * 100)) : 0,
            'resets_in' => $this->formatResetsIn($resetTime),
            'over_limit' => $limit > 0 && $cost > $limit,
        ];
    }

    private function costForPeriod(CarbonImmutable $start, CarbonImmutable $end): float
    {
        return (float) AiUsage::query()
            ->glc()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->sum('cost');
    }

    private function resetsAt(CarbonImmutable $now, CarbonImmutable $periodStart, ?int $hours, ?int $days): CarbonImmutable
    {
        $oldest = AiUsage::query()
            ->glc()
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<=', $now)
            ->min('created_at');

        if (! is_string($oldest)) {
            if ($hours !== null) {
                return $now->addHours($hours);
            }

            return $now->addDays((int) $days);
        }

        $oldestAt = CarbonImmutable::parse($oldest);

        if ($hours !== null) {
            return $oldestAt->addHours($hours);
        }

        return $oldestAt->addDays((int) $days);
    }

    /**
     * @return list<array{agent: string, label: string, cost_usd: float, requests: int}>
     */
    private function usageByAgent(CarbonImmutable $start, CarbonImmutable $end): array
    {
        /** @var list<object{agent: string, cost_usd: string|float, requests: int|string}> $rows */
        $rows = AiUsage::query()
            ->glc()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->select([
                'agent',
                DB::raw('SUM(cost) as cost_usd'),
                DB::raw('COUNT(*) as requests'),
            ])
            ->groupBy('agent')
            ->orderByDesc('cost_usd')
            ->get()
            ->all();

        return array_map(fn (object $row): array => [
            'agent' => $row->agent,
            'label' => $this->agentLabel($row->agent),
            'cost_usd' => round((float) $row->cost_usd, 4),
            'requests' => (int) $row->requests,
        ], $rows);
    }

    /**
     * @return list<array{date: string, cost_usd: float, requests: int}>
     */
    private function recentDailyTotals(CarbonImmutable $start, CarbonImmutable $end): array
    {
        /** @var list<object{day: string, cost_usd: string|float, requests: int|string}> $rows */
        $rows = AiUsage::query()
            ->glc()
            ->where('created_at', '>=', $start->startOfDay())
            ->where('created_at', '<=', $end)
            ->select([
                DB::raw('DATE(created_at) as day'),
                DB::raw('SUM(cost) as cost_usd'),
                DB::raw('COUNT(*) as requests'),
            ])
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->all();

        return array_map(fn (object $row): array => [
            'date' => $row->day,
            'cost_usd' => round((float) $row->cost_usd, 4),
            'requests' => (int) $row->requests,
        ], $rows);
    }

    private function agentLabel(string $agentClass): string
    {
        $basename = class_basename($agentClass);

        return str($basename)
            ->replace('Agent', '')
            ->headline()
            ->toString();
    }

    private function formatResetsIn(CarbonImmutable $resetTime): string
    {
        $now = CarbonImmutable::now();
        $diff = $now->diff($resetTime);

        if ($diff->d > 0) {
            return $diff->d.' days '.$diff->h.' hours';
        }

        if ($diff->h > 0) {
            return $diff->h.' hours '.$diff->i.' minutes';
        }

        return $diff->i.' minutes';
    }
}
