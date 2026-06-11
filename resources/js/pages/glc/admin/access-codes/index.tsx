import GlcLayout from '@/layouts/glc-layout';
import { Head, router, useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import {
    Badge,
    buttonPrimaryClass,
    buttonSecondaryClass,
    ConfirmDialog,
    Field,
    inputClass,
    Modal,
    type Option,
    Pagination,
    type Paginator,
    StatusBanner,
} from '../components';

interface AccessCode {
    id: number;
    code: string;
    status: string;
    status_label: string;
    is_expired: boolean;
    expires_at: string | null;
    revoked_at: string | null;
    note: string | null;
    issuer_name: string | null;
    attempts_count: number;
    can_revoke: boolean;
    created_at: string;
}

interface AccessCodesIndexProps {
    codes: Paginator<AccessCode>;
    filters: { status: string | null; search: string };
    statuses: Option[];
    status?: string | null;
}

function statusTone(status: string): 'green' | 'blue' | 'slate' | 'red' {
    switch (status) {
        case 'unused':
            return 'green';
        case 'in_progress':
            return 'blue';
        case 'completed':
            return 'slate';
        default:
            return 'red';
    }
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '-';
}

export default function AccessCodesIndex({
    codes,
    filters,
    statuses,
    status,
}: AccessCodesIndexProps) {
    const [createOpen, setCreateOpen] = useState(false);
    const [revoking, setRevoking] = useState<AccessCode | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const createForm = useForm({
        quantity: '1',
        expires_at: '',
        note: '',
    });

    const applyFilters = (overrides: Partial<{ status: string }> = {}) => {
        router.get(
            '/admin/access-codes',
            {
                search: search || undefined,
                status: overrides.status ?? filters.status ?? undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const submitCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.post('/admin/access-codes', {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                setCreateOpen(false);
            },
        });
    };

    const confirmRevoke = () => {
        if (!revoking) {
            return;
        }

        router.patch(
            `/admin/access-codes/${revoking.id}/revoke`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setRevoking(null),
            },
        );
    };

    return (
        <GlcLayout title="Placement Access Codes">
            <Head title="Access Codes" />

            <StatusBanner message={status} />

            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        applyFilters();
                    }}
                    className="flex flex-1 flex-col gap-2 sm:flex-row"
                >
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search code"
                        aria-label="Search codes"
                        className={`${inputClass} sm:max-w-xs`}
                    />
                    <select
                        value={filters.status ?? ''}
                        onChange={(e) =>
                            applyFilters({
                                status: e.target.value || undefined,
                            })
                        }
                        aria-label="Filter by status"
                        className={`${inputClass} sm:max-w-44`}
                    >
                        <option value="">All statuses</option>
                        {statuses.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                    <button type="submit" className={buttonSecondaryClass}>
                        Search
                    </button>
                </form>

                <button
                    type="button"
                    onClick={() => setCreateOpen(true)}
                    className={buttonPrimaryClass}
                >
                    New codes
                </button>
            </div>

            <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table className="w-full min-w-[720px] text-left text-sm">
                    <thead className="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th className="px-4 py-3">Code</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3">Expires</th>
                            <th className="px-4 py-3">Note</th>
                            <th className="px-4 py-3">Issued by</th>
                            <th className="px-4 py-3">Attempts</th>
                            <th className="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {codes.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-4 py-8 text-center text-slate-500"
                                >
                                    No access codes found.
                                </td>
                            </tr>
                        )}
                        {codes.data.map((code) => (
                            <tr key={code.id}>
                                <td className="px-4 py-3 font-mono text-sm font-semibold tracking-wide text-slate-900">
                                    {code.code}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-1">
                                        <Badge tone={statusTone(code.status)}>
                                            {code.status_label}
                                        </Badge>
                                        {code.is_expired &&
                                            code.status === 'unused' && (
                                                <Badge tone="red">
                                                    Expired
                                                </Badge>
                                            )}
                                    </div>
                                </td>
                                <td className="px-4 py-3 text-slate-600">
                                    {formatDate(code.expires_at)}
                                </td>
                                <td className="max-w-40 truncate px-4 py-3 text-slate-600">
                                    {code.note ?? '-'}
                                </td>
                                <td className="px-4 py-3 text-slate-600">
                                    {code.issuer_name ?? '-'}
                                </td>
                                <td className="px-4 py-3 text-slate-600">
                                    {code.attempts_count}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    {code.can_revoke && (
                                        <button
                                            type="button"
                                            onClick={() => setRevoking(code)}
                                            className="text-sm font-medium text-red-600 hover:underline"
                                        >
                                            Revoke
                                        </button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination paginator={codes} />

            <Modal
                open={createOpen}
                title="Create access codes"
                onClose={() => setCreateOpen(false)}
            >
                <form onSubmit={submitCreate} className="space-y-4">
                    <Field
                        label="Quantity"
                        htmlFor="quantity"
                        error={createForm.errors.quantity}
                        hint="Create 1-100 codes in one batch."
                    >
                        <input
                            id="quantity"
                            type="number"
                            min={1}
                            max={100}
                            value={createForm.data.quantity}
                            onChange={(e) =>
                                createForm.setData('quantity', e.target.value)
                            }
                            className={inputClass}
                            required
                        />
                    </Field>

                    <Field
                        label="Expires at (optional)"
                        htmlFor="expires_at"
                        error={createForm.errors.expires_at}
                    >
                        <input
                            id="expires_at"
                            type="datetime-local"
                            value={createForm.data.expires_at}
                            onChange={(e) =>
                                createForm.setData('expires_at', e.target.value)
                            }
                            className={inputClass}
                        />
                    </Field>

                    <Field
                        label="Note (optional)"
                        htmlFor="note"
                        error={createForm.errors.note}
                        hint="Internal note, e.g. intake batch or candidate name."
                    >
                        <input
                            id="note"
                            type="text"
                            maxLength={255}
                            value={createForm.data.note}
                            onChange={(e) =>
                                createForm.setData('note', e.target.value)
                            }
                            className={inputClass}
                        />
                    </Field>

                    <div className="flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={() => setCreateOpen(false)}
                            className={buttonSecondaryClass}
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={createForm.processing}
                            className={buttonPrimaryClass}
                        >
                            Create
                        </button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                open={revoking !== null}
                title="Revoke access code"
                message={`Revoke code ${revoking?.code ?? ''}? It can no longer start a placement session and cannot be un-revoked.`}
                confirmLabel="Revoke"
                danger
                onConfirm={confirmRevoke}
                onCancel={() => setRevoking(null)}
            />
        </GlcLayout>
    );
}
