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
        <GlcLayout title="Audit Log">
            <Head title="Audit Log" />

            <div className="mb-4 sm:max-w-xs">
                <select
                    value={filters.action ?? ''}
                    onChange={(e) => applyFilter(e.target.value)}
                    aria-label="Filter by action"
                    className={inputClass}
                >
                    <option value="">All actions</option>
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
                            <th className="px-4 py-3">Timestamp</th>
                            <th className="px-4 py-3">Actor</th>
                            <th className="px-4 py-3">Action</th>
                            <th className="px-4 py-3">Subject</th>
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
                                    No audit entries found.
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
                                        <code className="block max-w-md text-xs break-all text-slate-500">
                                            {JSON.stringify(entry.details)}
                                        </code>
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
