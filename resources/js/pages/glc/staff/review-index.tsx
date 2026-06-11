import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Badge, btnPrimary, btnSecondary, inputCls } from './ui';

interface ReviewRow {
    id: number;
    candidate_name: string;
    candidate_email: string;
    candidate_age: number;
    is_minor: boolean;
    submitted_at: string | null;
    status: string;
    status_label: string;
    flags: string[];
    has_integrity_events: boolean;
    suggested_level: string | null;
    variance_flagged: boolean;
    assignee: string | null;
    assigned_to: number | null;
    can_claim: boolean;
}

interface PageProps {
    reviews: {
        data: ReviewRow[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    };
    filters: Record<string, string | undefined>;
    staff: { id: number; name: string }[];
    supervises: boolean;
    [key: string]: unknown;
}

const STATUS_TONES: Record<string, 'amber' | 'blue' | 'emerald' | 'slate'> = {
    pending: 'amber',
    in_review: 'blue',
    approved: 'emerald',
    sent: 'slate',
};

export default function ReviewIndex() {
    const { reviews, filters, staff, supervises } = usePage<PageProps>().props;
    const [form, setForm] = useState({
        status: filters.status ?? '',
        assignee: filters.assignee ?? '',
        flag: filters.flag ?? '',
        from: filters.from ?? '',
        to: filters.to ?? '',
        search: filters.search ?? '',
    });

    const applyFilters = () => {
        const params = Object.fromEntries(
            Object.entries(form).filter(([, value]) => value !== ''),
        );
        router.get('/staff/review', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <GlcLayout title="Review Queue">
            <Head title="Review Queue" />

            <div className="mb-4 grid grid-cols-2 gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-3 lg:grid-cols-7">
                <select
                    className={inputCls}
                    value={form.status}
                    onChange={(e) =>
                        setForm({ ...form, status: e.target.value })
                    }
                >
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_review">In review</option>
                    <option value="approved">Approved</option>
                    <option value="sent">Sent</option>
                </select>
                <select
                    className={inputCls}
                    value={form.assignee}
                    onChange={(e) =>
                        setForm({ ...form, assignee: e.target.value })
                    }
                >
                    <option value="">Any assignee</option>
                    <option value="me">Assigned to me</option>
                    <option value="unassigned">Unassigned</option>
                    {supervises &&
                        staff.map((member) => (
                            <option key={member.id} value={String(member.id)}>
                                {member.name}
                            </option>
                        ))}
                </select>
                <select
                    className={inputCls}
                    value={form.flag}
                    onChange={(e) => setForm({ ...form, flag: e.target.value })}
                >
                    <option value="">Any flags</option>
                    <option value="variance">Variance</option>
                    <option value="integrity">Integrity</option>
                </select>
                <input
                    type="date"
                    className={inputCls}
                    value={form.from}
                    onChange={(e) => setForm({ ...form, from: e.target.value })}
                />
                <input
                    type="date"
                    className={inputCls}
                    value={form.to}
                    onChange={(e) => setForm({ ...form, to: e.target.value })}
                />
                <input
                    className={inputCls}
                    placeholder="Search candidate…"
                    value={form.search}
                    onChange={(e) =>
                        setForm({ ...form, search: e.target.value })
                    }
                    onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                />
                <button
                    type="button"
                    className={btnPrimary}
                    onClick={applyFilters}
                >
                    Filter
                </button>
            </div>

            <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table className="w-full min-w-[760px] text-left text-sm">
                    <thead className="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th className="px-3 py-2">Candidate</th>
                            <th className="px-3 py-2">Submitted</th>
                            <th className="px-3 py-2">Status</th>
                            <th className="px-3 py-2">Flags</th>
                            <th className="px-3 py-2">Suggested</th>
                            <th className="px-3 py-2">Assignee</th>
                            <th className="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        {reviews.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-3 py-8 text-center text-slate-400"
                                >
                                    No submissions match these filters.
                                </td>
                            </tr>
                        )}
                        {reviews.data.map((review) => (
                            <tr
                                key={review.id}
                                className="border-b border-slate-100 hover:bg-slate-50"
                            >
                                <td className="px-3 py-2">
                                    <div className="font-medium text-slate-800">
                                        {review.candidate_name}
                                        {review.is_minor && (
                                            <span className="ml-1.5">
                                                <Badge tone="amber">
                                                    Minor
                                                </Badge>
                                            </span>
                                        )}
                                    </div>
                                    <div className="text-xs text-slate-500">
                                        {review.candidate_email} · age{' '}
                                        {review.candidate_age}
                                    </div>
                                </td>
                                <td className="px-3 py-2 text-xs whitespace-nowrap text-slate-600">
                                    {review.submitted_at ?? '—'}
                                </td>
                                <td className="px-3 py-2">
                                    <Badge
                                        tone={
                                            STATUS_TONES[review.status] ??
                                            'slate'
                                        }
                                    >
                                        {review.status_label}
                                    </Badge>
                                </td>
                                <td className="px-3 py-2">
                                    <div className="flex flex-wrap gap-1">
                                        {review.flags.includes('variance') && (
                                            <Badge tone="red">Variance</Badge>
                                        )}
                                        {(review.flags.includes('integrity') ||
                                            review.has_integrity_events) && (
                                            <Badge tone="red">Integrity</Badge>
                                        )}
                                    </div>
                                </td>
                                <td className="px-3 py-2 text-slate-700">
                                    {review.suggested_level ?? '—'}
                                </td>
                                <td className="px-3 py-2 text-xs text-slate-600">
                                    {review.assignee ?? 'Unassigned'}
                                </td>
                                <td className="px-3 py-2">
                                    <div className="flex justify-end gap-2">
                                        {review.can_claim && (
                                            <button
                                                type="button"
                                                className={btnSecondary}
                                                onClick={() =>
                                                    router.post(
                                                        `/staff/review/${review.id}/claim`,
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Claim
                                            </button>
                                        )}
                                        <Link
                                            href={`/staff/review/${review.id}`}
                                            className={btnPrimary}
                                        >
                                            Open
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="mt-3 flex flex-wrap items-center gap-1">
                {reviews.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            className={`rounded-md px-2.5 py-1 text-xs ${
                                link.active
                                    ? 'bg-emerald-600 text-white'
                                    : 'text-slate-600 hover:bg-slate-100'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            key={index}
                            className="px-2.5 py-1 text-xs text-slate-300"
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ),
                )}
                <span className="ml-auto text-xs text-slate-400">
                    {reviews.total} total
                </span>
            </div>
        </GlcLayout>
    );
}
