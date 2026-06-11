import GlcLayout from '@/layouts/glc-layout';
import { Head, router } from '@inertiajs/react';
import {
    Badge,
    inputClass,
    Pagination,
    type Option,
    type Paginator,
} from '../components';

interface AuditEntry {
    id: number;
    action: string;
    action_label: string;
    actor_name: string | null;
    actor_email: string | null;
    subject: string | null;
    details: Record<string, unknown> | null;
    created_at: string;
}

interface AuditIndexProps {
    logs: Paginator<AuditEntry>;
    filters: { action: string | null };
    actions: Option[];
}

function detailValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '-';
    }

    if (typeof value === 'string') {
        return value;
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    if (Array.isArray(value)) {
        return value.map((entry) => detailValue(entry)).join(', ');
    }

    return JSON.stringify(value);
}

export default function AuditIndex({
    logs,
    filters,
    actions,
}: AuditIndexProps) {
    const applyFilter = (action: string) => {
        router.get(
            '/admin/audit',
            { action: action || undefined },
            { preserveState: true, replace: true },
        );
    };

    return (
        <GlcLayout title="Activity Log">
            <Head title="Activity Log" />

            <p className="-mt-2 mb-4 text-sm text-slate-600">
                Who did what, and when. Every sensitive action is recorded here
                and cannot be edited or deleted.
            </p>

            <div className="mb-4 sm:max-w-xs">
                <select
                    value={filters.action ?? ''}
                    onChange={(e) => applyFilter(e.target.value)}
                    aria-label="Show only one kind of activity"
                    className={inputClass}
                >
                    <option value="">All activity</option>
                    {actions.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
            </div>

            <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table className="w-full min-w-[720px] text-left text-sm">
                    <thead className="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th className="px-4 py-3">When</th>
                            <th className="px-4 py-3">Who</th>
                            <th className="px-4 py-3">What they did</th>
                            <th className="px-4 py-3">Affected record</th>
                            <th className="px-4 py-3">Details</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {logs.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-slate-500"
                                >
                                    No activity recorded yet.
                                </td>
                            </tr>
                        )}
                        {logs.data.map((entry) => (
                            <tr key={entry.id} className="align-top">
                                <td className="px-4 py-3 whitespace-nowrap text-slate-600">
                                    {new Date(
                                        entry.created_at,
                                    ).toLocaleString()}
                                </td>
                                <td className="px-4 py-3">
                                    <p className="font-medium text-slate-900">
                                        {entry.actor_name ?? 'System'}
                                    </p>
                                    {entry.actor_email && (
                                        <p className="text-xs text-slate-500">
                                            {entry.actor_email}
                                        </p>
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    <Badge tone="blue">
                                        {entry.action_label}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3 text-slate-600">
                                    {entry.subject ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {entry.details ? (
                                        <dl className="max-w-md space-y-0.5 text-xs text-slate-500">
                                            {Object.entries(entry.details).map(
                                                ([key, value]) => (
                                                    <div
                                                        key={key}
                                                        className="flex gap-1"
                                                    >
                                                        <dt className="shrink-0 font-medium text-slate-600">
                                                            {key.replaceAll(
                                                                '_',
                                                                ' ',
                                                            )}
                                                            :
                                                        </dt>
                                                        <dd className="break-all">
                                                            {detailValue(value)}
                                                        </dd>
                                                    </div>
                                                ),
                                            )}
                                        </dl>
                                    ) : (
                                        <span className="text-slate-400">
                                            -
                                        </span>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination paginator={logs} />
        </GlcLayout>
    );
}
