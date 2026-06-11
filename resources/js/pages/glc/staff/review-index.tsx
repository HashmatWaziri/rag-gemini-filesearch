import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    ATTENTION_LABELS,
    nextStepHint,
    REVIEW_STATUS_LABELS,
    REVIEW_STATUS_TONES,
    type ReviewStatus,
} from './process-steps';
import { Badge, btnPrimary, btnSecondary, inputCls } from './ui';

interface ReviewRow {
    id: number;
    candidate_name: string;
    candidate_email: string;
    candidate_age: number;
    is_minor: boolean;
    submitted_at: string | null;
    status: ReviewStatus;
    status_label: string;
    flags: string[];
    has_integrity_events: boolean;
    suggested_level: string | null;
    variance_flagged: boolean;
    assignee: string | null;
    assigned_to: number | null;
    can_claim: boolean;
    has_decision: boolean;
    narrative_approved: boolean;
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

    const startReview = (id: number) => {
        router.post(
            `/staff/review/${id}/claim`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => router.visit(`/staff/review/${id}`),
            },
        );
    };

    return (
        <GlcLayout title="Placement Tests">
            <Head title="Placement Tests" />

            <p className="-mt-3 mb-4 text-sm text-slate-500">
                Placement tests waiting for GLC review and result delivery.
            </p>

            <div className="mb-4 grid grid-cols-2 gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-3 lg:grid-cols-7">
                <select
                    className={inputCls}
                    value={form.status}
                    onChange={(e) =>
                        setForm({ ...form, status: e.target.value })
                    }
                >
                    <option value="">All statuses</option>
                    <option value="pending">
                        {REVIEW_STATUS_LABELS.pending}
                    </option>
                    <option value="in_review">
                        {REVIEW_STATUS_LABELS.in_review}
                    </option>
                    <option value="approved">
                        {REVIEW_STATUS_LABELS.approved}
                    </option>
                    <option value="sent">{REVIEW_STATUS_LABELS.sent}</option>
                </select>
                <select
                    className={inputCls}
                    value={form.assignee}
                    onChange={(e) =>
                        setForm({ ...form, assignee: e.target.value })
                    }
                >
                    <option value="">All reviewers</option>
                    <option value="me">Assigned to me</option>
                    <option value="unassigned">No reviewer yet</option>
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
                    <option value="">All tests</option>
                    <option value="variance">
                        Needs attention:{' '}
                        {ATTENTION_LABELS.variance.toLowerCase()}
                    </option>
                    <option value="integrity">
                        Needs attention:{' '}
                        {ATTENTION_LABELS.integrity.toLowerCase()}
                    </option>
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
                    Apply filters
                </button>
            </div>

            <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table className="w-full min-w-[760px] text-left text-sm">
                    <thead className="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th className="px-3 py-2">Candidate</th>
                            <th className="px-3 py-2">Submitted</th>
                            <th className="px-3 py-2">Status</th>
                            <th className="px-3 py-2">Needs attention</th>
                            <th className="px-3 py-2">Suggested level</th>
                            <th className="px-3 py-2">Reviewer</th>
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
                                    No placement tests match these filters.
                                </td>
                            </tr>
                        )}
                        {reviews.data.map((review) => {
                            const hint = nextStepHint({
                                status: review.status,
                                levelConfirmed: review.has_decision,
                                summaryApproved: review.narrative_approved,
                            });

                            return (
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
                                                        Under 18
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
                                                REVIEW_STATUS_TONES[
                                                    review.status
                                                ] ?? 'slate'
                                            }
                                        >
                                            {REVIEW_STATUS_LABELS[
                                                review.status
                                            ] ?? review.status_label}
                                        </Badge>
                                        <p
                                            className={`mt-1 text-xs ${
                                                hint.done
                                                    ? 'font-medium text-emerald-700'
                                                    : 'text-slate-500'
                                            }`}
                                        >
                                            {hint.done && (
                                                <span aria-hidden>✓ </span>
                                            )}
                                            {hint.label}
                                        </p>
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex flex-wrap gap-1">
                                            {review.flags.includes(
                                                'variance',
                                            ) && (
                                                <Badge tone="red">
                                                    {ATTENTION_LABELS.variance}
                                                </Badge>
                                            )}
                                            {(review.flags.includes(
                                                'integrity',
                                            ) ||
                                                review.has_integrity_events) && (
                                                <Badge tone="red">
                                                    {ATTENTION_LABELS.integrity}
                                                </Badge>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-3 py-2 text-slate-700">
                                        {review.suggested_level ?? '—'}
                                    </td>
                                    <td className="px-3 py-2 text-xs text-slate-600">
                                        {review.assignee ?? 'No reviewer yet'}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex items-start justify-end gap-2">
                                            {review.can_claim && (
                                                <div className="flex flex-col items-center">
                                                    <button
                                                        type="button"
                                                        className={btnSecondary}
                                                        onClick={() =>
                                                            startReview(
                                                                review.id,
                                                            )
                                                        }
                                                    >
                                                        Start review
                                                    </button>
                                                    <span className="mt-0.5 text-[11px] text-slate-400">
                                                        assigns it to you
                                                    </span>
                                                </div>
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
                            );
                        })}
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
