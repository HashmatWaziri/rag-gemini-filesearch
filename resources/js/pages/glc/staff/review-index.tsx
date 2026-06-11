import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    ATTENTION_LABELS,
    nextStepHint,
    REVIEW_STATUS_LABELS,
    type ReviewStatus,
} from './process-steps';
import {
    Badge,
    btnPrimary,
    btnSecondary,
    CheckIcon,
    inputCls,
    ScoreBars,
    submissionAge,
} from './ui';

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
    section_scores?: Record<string, number | null> | null;
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

const STATUS_DOT: Record<ReviewStatus, string> = {
    pending: 'bg-amber-500',
    in_review: 'bg-blue-500',
    approved: 'bg-emerald-500',
    sent: 'bg-slate-400',
};

function StatusPill({ status }: { status: ReviewStatus }) {
    return (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-xs font-medium text-slate-700">
            <span
                aria-hidden
                className={`h-1.5 w-1.5 rounded-full ${STATUS_DOT[status] ?? 'bg-slate-400'}`}
            />
            {REVIEW_STATUS_LABELS[status] ?? status}
        </span>
    );
}

function AttentionBadges({ review }: { review: ReviewRow }) {
    const showsIntegrity =
        review.flags.includes('integrity') || review.has_integrity_events;

    if (!review.flags.includes('variance') && !showsIntegrity) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-1">
            {review.flags.includes('variance') && (
                <Badge tone="red">{ATTENTION_LABELS.variance}</Badge>
            )}
            {showsIntegrity && (
                <Badge tone="red">{ATTENTION_LABELS.integrity}</Badge>
            )}
        </div>
    );
}

function AgeCue({ submittedAt }: { submittedAt: string | null }) {
    const age = submissionAge(submittedAt);

    if (!age) {
        return null;
    }

    const tones = {
        slate: 'text-slate-400',
        amber: 'text-amber-600',
        red: 'text-red-600 font-medium',
    } as const;

    return <span className={`text-xs ${tones[age.tone]}`}>{age.label}</span>;
}

function RowActions({
    review,
    onStart,
}: {
    review: ReviewRow;
    onStart: (id: number) => void;
}) {
    return (
        <div className="flex items-center justify-end gap-2">
            {review.can_claim ? (
                <button
                    type="button"
                    className={btnPrimary}
                    onClick={() => onStart(review.id)}
                    title="Assigns this test to you and opens it"
                >
                    Start review
                </button>
            ) : (
                <Link
                    href={`/staff/review/${review.id}`}
                    className={
                        review.status === 'sent' ? btnSecondary : btnPrimary
                    }
                >
                    Open
                </Link>
            )}
            {review.can_claim && (
                <Link
                    href={`/staff/review/${review.id}`}
                    className={btnSecondary}
                >
                    Open
                </Link>
            )}
        </div>
    );
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

    const activeFilterCount = Object.values(filters).filter(
        (value) => value !== undefined && value !== '',
    ).length;
    const hasActiveFilters = activeFilterCount > 0;

    const applyFilters = () => {
        const params = Object.fromEntries(
            Object.entries(form).filter(([, value]) => value !== ''),
        );
        router.get('/staff/review', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        setForm({
            status: '',
            assignee: '',
            flag: '',
            from: '',
            to: '',
            search: '',
        });
        router.get(
            '/staff/review',
            {},
            { preserveState: true, preserveScroll: true },
        );
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

    const filterField = (active: boolean) =>
        active ? `${inputCls} border-emerald-400 bg-emerald-50/40` : inputCls;

    return (
        <GlcLayout title="Placement Tests">
            <Head title="Placement Tests" />

            <p className="-mt-3 mb-4 text-sm text-slate-500">
                Placement tests waiting for GLC review and result delivery.
            </p>

            <section
                aria-label="Filters"
                className="mb-4 rounded-xl border border-slate-200 bg-white p-3 shadow-sm"
            >
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                    <label className="block">
                        <span className="mb-1 block text-[11px] font-medium tracking-wide text-slate-500 uppercase">
                            Status
                        </span>
                        <select
                            className={filterField(form.status !== '')}
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
                            <option value="sent">
                                {REVIEW_STATUS_LABELS.sent}
                            </option>
                        </select>
                    </label>
                    <label className="block">
                        <span className="mb-1 block text-[11px] font-medium tracking-wide text-slate-500 uppercase">
                            Reviewer
                        </span>
                        <select
                            className={filterField(form.assignee !== '')}
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
                                    <option
                                        key={member.id}
                                        value={String(member.id)}
                                    >
                                        {member.name}
                                    </option>
                                ))}
                        </select>
                    </label>
                    <label className="block">
                        <span className="mb-1 block text-[11px] font-medium tracking-wide text-slate-500 uppercase">
                            Needs attention
                        </span>
                        <select
                            className={filterField(form.flag !== '')}
                            value={form.flag}
                            onChange={(e) =>
                                setForm({ ...form, flag: e.target.value })
                            }
                        >
                            <option value="">All tests</option>
                            <option value="variance">
                                {ATTENTION_LABELS.variance}
                            </option>
                            <option value="integrity">
                                {ATTENTION_LABELS.integrity}
                            </option>
                        </select>
                    </label>
                    <label className="block">
                        <span className="mb-1 block text-[11px] font-medium tracking-wide text-slate-500 uppercase">
                            Submitted from
                        </span>
                        <input
                            type="date"
                            className={filterField(form.from !== '')}
                            value={form.from}
                            onChange={(e) =>
                                setForm({ ...form, from: e.target.value })
                            }
                        />
                    </label>
                    <label className="block">
                        <span className="mb-1 block text-[11px] font-medium tracking-wide text-slate-500 uppercase">
                            Submitted to
                        </span>
                        <input
                            type="date"
                            className={filterField(form.to !== '')}
                            value={form.to}
                            onChange={(e) =>
                                setForm({ ...form, to: e.target.value })
                            }
                        />
                    </label>
                    <label className="block">
                        <span className="mb-1 block text-[11px] font-medium tracking-wide text-slate-500 uppercase">
                            Search
                        </span>
                        <input
                            className={filterField(form.search !== '')}
                            placeholder="Name or email…"
                            value={form.search}
                            onChange={(e) =>
                                setForm({ ...form, search: e.target.value })
                            }
                            onKeyDown={(e) =>
                                e.key === 'Enter' && applyFilters()
                            }
                        />
                    </label>
                </div>
                <div className="mt-2 flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        className={btnPrimary}
                        onClick={applyFilters}
                    >
                        Apply filters
                    </button>
                    {hasActiveFilters && (
                        <>
                            <button
                                type="button"
                                className={btnSecondary}
                                onClick={clearFilters}
                            >
                                Clear
                            </button>
                            <span className="text-xs text-emerald-700">
                                {activeFilterCount}{' '}
                                {activeFilterCount === 1 ? 'filter' : 'filters'}{' '}
                                active
                            </span>
                        </>
                    )}
                    <span className="ml-auto text-xs text-slate-400">
                        {reviews.total} {reviews.total === 1 ? 'test' : 'tests'}
                    </span>
                </div>
            </section>

            {reviews.data.length === 0 ? (
                <div className="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
                    <p className="text-sm font-medium text-slate-600">
                        {hasActiveFilters
                            ? 'No placement tests match these filters.'
                            : 'No placement tests to review yet.'}
                    </p>
                    <p className="mt-1 text-xs text-slate-400">
                        {hasActiveFilters
                            ? 'Try widening the filters or clearing them.'
                            : 'New submissions appear here as soon as candidates finish the test.'}
                    </p>
                    {hasActiveFilters && (
                        <button
                            type="button"
                            className={`${btnSecondary} mt-4`}
                            onClick={clearFilters}
                        >
                            Clear all filters
                        </button>
                    )}
                </div>
            ) : (
                <>
                    {/* Mobile: card list */}
                    <ul className="space-y-3 md:hidden">
                        {reviews.data.map((review) => {
                            const hint = nextStepHint({
                                status: review.status,
                                levelConfirmed: review.has_decision,
                                summaryApproved: review.narrative_approved,
                            });

                            return (
                                <li
                                    key={review.id}
                                    className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="truncate font-medium text-slate-900">
                                                {review.candidate_name}
                                            </p>
                                            <p className="truncate text-xs text-slate-500">
                                                {review.candidate_email} · age{' '}
                                                {review.candidate_age}
                                            </p>
                                        </div>
                                        <StatusPill status={review.status} />
                                    </div>
                                    <div className="mt-2 flex flex-wrap items-center gap-2">
                                        {review.is_minor && (
                                            <Badge tone="amber">Under 18</Badge>
                                        )}
                                        <AttentionBadges review={review} />
                                        <AgeCue
                                            submittedAt={review.submitted_at}
                                        />
                                    </div>
                                    <div className="mt-3 flex items-center justify-between gap-3">
                                        <div>
                                            <ScoreBars
                                                scores={review.section_scores}
                                            />
                                            <p className="mt-1 text-xs text-slate-500">
                                                {review.suggested_level
                                                    ? `Suggested: ${review.suggested_level}`
                                                    : 'No suggestion yet'}
                                            </p>
                                        </div>
                                        <RowActions
                                            review={review}
                                            onStart={startReview}
                                        />
                                    </div>
                                    <p
                                        className={`mt-2 text-xs ${
                                            hint.done
                                                ? 'inline-flex items-center gap-1 font-medium text-emerald-700'
                                                : 'text-slate-500'
                                        }`}
                                    >
                                        {hint.done && (
                                            <CheckIcon className="h-3 w-3" />
                                        )}
                                        {hint.label}
                                    </p>
                                </li>
                            );
                        })}
                    </ul>

                    {/* Desktop: table */}
                    <div className="hidden overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm md:block">
                        <table className="w-full min-w-[860px] text-left text-sm">
                            <thead className="border-b border-slate-200 bg-slate-50 text-[11px] tracking-wide text-slate-500 uppercase">
                                <tr>
                                    <th scope="col" className="px-3 py-2.5">
                                        Candidate
                                    </th>
                                    <th scope="col" className="px-3 py-2.5">
                                        Submitted
                                    </th>
                                    <th scope="col" className="px-3 py-2.5">
                                        Status
                                    </th>
                                    <th scope="col" className="px-3 py-2.5">
                                        Sections
                                    </th>
                                    <th scope="col" className="px-3 py-2.5">
                                        Suggested level
                                    </th>
                                    <th scope="col" className="px-3 py-2.5">
                                        Reviewer
                                    </th>
                                    <th scope="col" className="px-3 py-2.5">
                                        <span className="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {reviews.data.map((review) => {
                                    const hint = nextStepHint({
                                        status: review.status,
                                        levelConfirmed: review.has_decision,
                                        summaryApproved:
                                            review.narrative_approved,
                                    });

                                    return (
                                        <tr
                                            key={review.id}
                                            className="border-b border-slate-100 transition-colors last:border-b-0 hover:bg-slate-50/70"
                                        >
                                            <td className="px-3 py-3">
                                                <div className="flex items-center gap-1.5 font-medium text-slate-800">
                                                    <span>
                                                        {review.candidate_name}
                                                    </span>
                                                    {review.is_minor && (
                                                        <Badge tone="amber">
                                                            Under 18
                                                        </Badge>
                                                    )}
                                                </div>
                                                <div className="text-xs text-slate-500">
                                                    {review.candidate_email} ·
                                                    age {review.candidate_age}
                                                </div>
                                                <div className="mt-1">
                                                    <AttentionBadges
                                                        review={review}
                                                    />
                                                </div>
                                            </td>
                                            <td className="px-3 py-3 align-top whitespace-nowrap">
                                                <span className="block text-xs text-slate-600">
                                                    {review.submitted_at ?? '—'}
                                                </span>
                                                <AgeCue
                                                    submittedAt={
                                                        review.submitted_at
                                                    }
                                                />
                                            </td>
                                            <td className="px-3 py-3 align-top">
                                                <StatusPill
                                                    status={review.status}
                                                />
                                                <p
                                                    className={`mt-1.5 text-xs ${
                                                        hint.done
                                                            ? 'inline-flex items-center gap-1 font-medium text-emerald-700'
                                                            : 'text-slate-500'
                                                    }`}
                                                >
                                                    {hint.done && (
                                                        <CheckIcon className="h-3 w-3" />
                                                    )}
                                                    {hint.label}
                                                </p>
                                            </td>
                                            <td className="px-3 py-3 align-top">
                                                <ScoreBars
                                                    scores={
                                                        review.section_scores
                                                    }
                                                />
                                            </td>
                                            <td className="px-3 py-3 align-top text-slate-700">
                                                {review.suggested_level ?? '—'}
                                                {review.variance_flagged && (
                                                    <p className="mt-0.5 text-[11px] text-red-600">
                                                        {
                                                            ATTENTION_LABELS.variance
                                                        }
                                                    </p>
                                                )}
                                            </td>
                                            <td className="px-3 py-3 align-top text-xs text-slate-600">
                                                {review.assignee ?? (
                                                    <span className="text-slate-400">
                                                        No reviewer yet
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-3 py-3 align-top">
                                                <RowActions
                                                    review={review}
                                                    onStart={startReview}
                                                />
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </>
            )}

            <nav
                aria-label="Pagination"
                className="mt-3 flex flex-wrap items-center gap-1"
            >
                {reviews.links.map((link, index) =>
                    link.url ? (
                        <Link
                            key={index}
                            href={link.url}
                            preserveScroll
                            className={`rounded-md px-2.5 py-1 text-xs ${
                                link.active
                                    ? 'bg-emerald-600 font-medium text-white'
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
            </nav>
        </GlcLayout>
    );
}
