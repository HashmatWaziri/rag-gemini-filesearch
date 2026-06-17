import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';
import {
    Badge,
    buttonPrimaryClass,
    buttonSecondaryClass,
    Field,
    inputClass,
    StatusBanner,
} from '../components';

interface UsageBucket {
    current: number;
    limit: number;
    current_usd: number;
    limit_usd: number;
    percentage: number;
    resets_in: string;
    over_limit: boolean;
}

interface AgentUsage {
    agent: string;
    label: string;
    cost_usd: number;
    requests: number;
}

interface DailyUsage {
    date: string;
    cost_usd: number;
    requests: number;
}

interface UsageDashboard {
    enforcement_enabled: boolean;
    credit_multiplier: number;
    rolling: UsageBucket;
    weekly: UsageBucket;
    by_agent: AgentUsage[];
    recent_days: DailyUsage[];
}

interface SettingsBounds {
    limit_usd: { min: number; max: number };
    rolling_period_hours: { min: number; max: number };
    weekly_period_days: { min: number; max: number };
}

interface AiCostSettingsProps {
    usage: UsageDashboard;
    settings: {
        defaults: Record<string, boolean | number>;
        effective: Record<string, boolean | number>;
        bounds: SettingsBounds;
    };
    status?: string | null;
}

function UsageWidget({
    title,
    bucket,
}: {
    title: string;
    bucket: UsageBucket;
}) {
    const percentage = Math.min(100, Math.max(0, bucket.percentage));
    const indicatorClass = bucket.over_limit || percentage >= 90
        ? 'bg-red-500'
        : percentage >= 70
          ? 'bg-yellow-500'
          : 'bg-green-500';

    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-sm">{title}</CardTitle>
                <CardDescription className="text-xs">
                    {bucket.current.toLocaleString()} /{' '}
                    {bucket.limit.toLocaleString()} credits
                    {bucket.over_limit && (
                        <span className="ml-1 font-medium text-destructive">
                            over limit
                        </span>
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent className="gap-2">
                <Progress
                    value={percentage}
                    className="h-2"
                    indicatorClassName={indicatorClass}
                />
                <p className="text-xs text-muted-foreground">
                    ${bucket.current_usd.toFixed(4)} / $
                    {bucket.limit_usd.toFixed(2)} USD · {percentage}% · resets
                    in {bucket.resets_in}
                </p>
            </CardContent>
        </Card>
    );
}

export default function AiCostSettings({
    usage,
    settings,
    status,
}: AiCostSettingsProps) {
    const form = useForm({
        enforcement_enabled: settings.effective.enforcement_enabled as boolean,
        rolling_limit_usd: String(settings.effective.rolling_limit_usd),
        rolling_period_hours: String(settings.effective.rolling_period_hours),
        weekly_limit_usd: String(settings.effective.weekly_limit_usd),
        weekly_period_days: String(settings.effective.weekly_period_days),
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put('/admin/settings/ai-cost', { preserveScroll: true });
    };

    const errors = form.errors as Record<string, string>;

    return (
        <GlcLayout title="AI Cost Controls">
            <Head title="AI Cost Controls" />

            <StatusBanner message={status} />

            <div className="space-y-6">
                <p className="text-sm text-secondary-foreground">
                    Platform-wide AI spend limits for GLC tutor and placement
                    workloads. Inspired by the Altani billing usage model — credits
                    are USD cost × {usage.credit_multiplier.toLocaleString()}.{' '}
                    <Link
                        href="/admin/settings/ai"
                        className="font-medium text-primary underline hover:text-primary/80"
                    >
                        AI model settings
                    </Link>
                </p>

                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-xs font-medium text-muted-foreground">
                        Enforcement
                    </span>
                    {usage.enforcement_enabled ? (
                        <Badge tone="green">Active</Badge>
                    ) : (
                        <Badge tone="amber">Monitoring only</Badge>
                    )}
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <UsageWidget
                        title={`Rolling (${settings.effective.rolling_period_hours}h)`}
                        bucket={usage.rolling}
                    />
                    <UsageWidget
                        title={`Weekly (${settings.effective.weekly_period_days}d)`}
                        bucket={usage.weekly}
                    />
                </div>

                <Card className="py-4">
                    <CardHeader>
                        <CardTitle className="text-base">
                            Spend by agent (weekly window)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {usage.by_agent.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No GLC AI usage recorded yet.
                            </p>
                        ) : (
                            <ul className="divide-y divide-border rounded-md border border-border text-sm">
                                {usage.by_agent.map((row) => (
                                    <li
                                        key={row.agent}
                                        className="flex items-center justify-between gap-3 px-3 py-2"
                                    >
                                        <span className="text-secondary-foreground">
                                            {row.label}
                                        </span>
                                        <span className="font-medium text-mono">
                                            ${row.cost_usd.toFixed(4)} ·{' '}
                                            {row.requests} calls
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                {usage.recent_days.length > 0 && (
                    <Card className="py-4">
                        <CardHeader>
                            <CardTitle className="text-base">
                                Last 7 days
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul className="divide-y divide-border rounded-md border border-border text-sm">
                                {usage.recent_days.map((row) => (
                                    <li
                                        key={row.date}
                                        className="flex items-center justify-between gap-3 px-3 py-2"
                                    >
                                        <span>{row.date}</span>
                                        <span className="font-medium text-mono">
                                            ${row.cost_usd.toFixed(4)} ·{' '}
                                            {row.requests} calls
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                )}

                <Card className="py-4">
                    <CardHeader>
                        <CardTitle className="text-base">Limits</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={form.data.enforcement_enabled}
                                    onChange={(e) =>
                                        form.setData(
                                            'enforcement_enabled',
                                            e.target.checked,
                                        )
                                    }
                                    className="rounded border-input text-primary focus:ring-ring"
                                />
                                Block new GLC AI calls when a limit would be
                                exceeded
                            </label>

                            <Field
                                label="Daily rolling limit (USD)"
                                htmlFor="rolling-limit"
                                error={errors.rolling_limit_usd}
                                hint={`Default: $${settings.defaults.rolling_limit_usd}. Allowed: $${settings.bounds.limit_usd.min}–$${settings.bounds.limit_usd.max}.`}
                            >
                                <input
                                    id="rolling-limit"
                                    type="number"
                                    min={settings.bounds.limit_usd.min}
                                    max={settings.bounds.limit_usd.max}
                                    step="0.01"
                                    value={form.data.rolling_limit_usd}
                                    onChange={(e) =>
                                        form.setData(
                                            'rolling_limit_usd',
                                            e.target.value,
                                        )
                                    }
                                    className={inputClass}
                                    required
                                />
                            </Field>

                            <Field
                                label="Rolling window (hours)"
                                htmlFor="rolling-hours"
                                error={errors.rolling_period_hours}
                            >
                                <input
                                    id="rolling-hours"
                                    type="number"
                                    min={settings.bounds.rolling_period_hours.min}
                                    max={settings.bounds.rolling_period_hours.max}
                                    value={form.data.rolling_period_hours}
                                    onChange={(e) =>
                                        form.setData(
                                            'rolling_period_hours',
                                            e.target.value,
                                        )
                                    }
                                    className={inputClass}
                                    required
                                />
                            </Field>

                            <Field
                                label="Weekly limit (USD)"
                                htmlFor="weekly-limit"
                                error={errors.weekly_limit_usd}
                                hint={`Default: $${settings.defaults.weekly_limit_usd}.`}
                            >
                                <input
                                    id="weekly-limit"
                                    type="number"
                                    min={settings.bounds.limit_usd.min}
                                    max={settings.bounds.limit_usd.max}
                                    step="0.01"
                                    value={form.data.weekly_limit_usd}
                                    onChange={(e) =>
                                        form.setData(
                                            'weekly_limit_usd',
                                            e.target.value,
                                        )
                                    }
                                    className={inputClass}
                                    required
                                />
                            </Field>

                            <Field
                                label="Weekly window (days)"
                                htmlFor="weekly-days"
                                error={errors.weekly_period_days}
                            >
                                <input
                                    id="weekly-days"
                                    type="number"
                                    min={settings.bounds.weekly_period_days.min}
                                    max={settings.bounds.weekly_period_days.max}
                                    value={form.data.weekly_period_days}
                                    onChange={(e) =>
                                        form.setData(
                                            'weekly_period_days',
                                            e.target.value,
                                        )
                                    }
                                    className={inputClass}
                                    required
                                />
                            </Field>

                            <div className="flex justify-end">
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className={buttonPrimaryClass}
                                >
                                    Save limits
                                </button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Link href="/admin/settings" className={buttonSecondaryClass}>
                    Back to settings
                </Link>
            </div>
        </GlcLayout>
    );
}
