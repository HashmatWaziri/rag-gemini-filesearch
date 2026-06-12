import { GlcDataTableCard, GlcSearchInput } from '@/components/glc';
import GlcLayout from '@/layouts/glc-layout';
import { cn } from '@/lib/utils';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { LinkPagination } from '../admin/components';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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
    approved: 'bg-primary',
    sent: 'bg-muted-foreground',
};

function StatusPill({ status }: { status: ReviewStatus }) {
    return (
        <span className="inline-flex items-center gap-1.5 rounded-full border border-border bg-card px-2.5 py-0.5 text-xs font-medium text-secondary-foreground">
            <span
                aria-hidden
                className={`h-1.5 w-1.5 rounded-full ${STATUS_DOT[status] ?? 'bg-muted-foreground'}`}
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
        slate: 'text-muted-foreground',
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
        active ? `${inputCls} border-primary/40 bg-primary/10` : inputCls;

    return (
        <GlcLayout title="Placement Tests">
            <Head title="Placement Tests" />

            <p className="-mt-3 mb-4 text-sm text-muted-foreground">
                Placement tests waiting for GLC review and result delivery.
            </p>

            <GlcDataTableCard
                filters={
                    <div className="flex w-full flex-col gap-3">
                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
                    <label className="block">
                        <span className="mb-1 block text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
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
                        <span className="mb-1 block text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
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
                        <span className="mb-1 block text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
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
                        <span className="mb-1 block text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
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
                        <span className="mb-1 block text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
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
                        <span className="mb-1 block text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
                            Search
                        </span>
                        <GlcSearchInput
                            value={form.search}
                            onValueChange={(value) =>
                                setForm({ ...form, search: value })
                            }
                            placeholder="Name or email…"
                            inputClassName={cn(
                                'w-full',
                                form.search !== '' &&
                                    'border-primary/40 bg-primary/10',
                            )}
                            onKeyDown={(e) =>
                                e.key === 'Enter' && applyFilters()
                            }
                        />
                    </label>
                </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Button type="button" onClick={applyFilters}>
                                Apply filters
                            </Button>
                            {hasActiveFilters && (
                                <>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={clearFilters}
                                    >
                                        Clear
                                    </Button>
                                    <span className="text-xs text-primary">
                                        {activeFilterCount}{' '}
                                        {activeFilterCount === 1
                                            ? 'filter'
                                            : 'filters'}{' '}
                                        active
                                    </span>
                                </>
                            )}
                            <span className="ml-auto text-xs text-muted-foreground">
                                {reviews.total}{' '}
                                {reviews.total === 1 ? 'test' : 'tests'}
                            </span>
                        </div>
                    </div>
                }
                footer={
                    reviews.data.length > 0 ? (
                        <LinkPagination paginator={reviews} />
                    ) : undefined
                }
            >
            {reviews.data.length === 0 ? (
                <div className="rounded-xl border border-dashed border-input bg-card px-6 py-12 text-center">
                    <p className="text-sm font-medium text-secondary-foreground">
                        {hasActiveFilters
                            ? 'No placement tests match these filters.'
                            : 'No placement tests to review yet.'}
                    </p>
                    <p className="mt-1 text-xs text-muted-foreground">
                        {hasActiveFilters
                            ? 'Try widening the filters or clearing them.'
                            : 'New submissions appear here as soon as candidates finish the test.'}
                    </p>
                    {hasActiveFilters && (
                        <Button
                            type="button"
                            variant="outline"
                            className="mt-4"
                            onClick={clearFilters}
                        >
                            Clear all filters
                        </Button>
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
                                    className="rounded-xl border border-border bg-card p-4 shadow-sm"
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="truncate font-medium text-foreground">
                                                {review.candidate_name}
                                            </p>
                                            <p className="truncate text-xs text-muted-foreground">
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
                                            <p className="mt-1 text-xs text-muted-foreground">
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
                                                ? 'inline-flex items-center gap-1 font-medium text-primary'
                                                : 'text-muted-foreground'
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
                    <div className="hidden md:block">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/50 hover:bg-muted/50">
                                    <TableHead className="text-[11px] tracking-wide uppercase">
                                        Candidate
                                    </TableHead>
                                    <TableHead className="text-[11px] tracking-wide uppercase">
                                        Submitted
                                    </TableHead>
                                    <TableHead className="text-[11px] tracking-wide uppercase">
                                        Status
                                    </TableHead>
                                    <TableHead className="text-[11px] tracking-wide uppercase">
                                        Sections
                                    </TableHead>
                                    <TableHead className="text-[11px] tracking-wide uppercase">
                                        Suggested level
                                    </TableHead>
                                    <TableHead className="text-[11px] tracking-wide uppercase">
                                        Reviewer
                                    </TableHead>
                                    <TableHead className="text-[11px] tracking-wide uppercase">
                                        <span className="sr-only">Actions</span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {reviews.data.map((review) => {
                                    const hint = nextStepHint({
                                        status: review.status,
                                        levelConfirmed: review.has_decision,
                                        summaryApproved:
                                            review.narrative_approved,
                                    });

                                    return (
                                        <TableRow
                                            key={review.id}
                                            className="hover:bg-accent/50"
                                        >
                                            <TableCell>
                                                <div className="flex items-center gap-1.5 font-medium text-mono">
                                                    <span>
                                                        {review.candidate_name}
                                                    </span>
                                                    {review.is_minor && (
                                                        <Badge tone="amber">
                                                            Under 18
                                                        </Badge>
                                                    )}
                                                </div>
                                                <div className="text-xs text-muted-foreground">
                                                    {review.candidate_email} ·
                                                    age {review.candidate_age}
                                                </div>
                                                <div className="mt-1">
                                                    <AttentionBadges
                                                        review={review}
                                                    />
                                                </div>
                                            </TableCell>
                                            <TableCell className="align-top whitespace-nowrap">
                                                <span className="block text-xs text-secondary-foreground">
                                                    {review.submitted_at ?? '—'}
                                                </span>
                                                <AgeCue
                                                    submittedAt={
                                                        review.submitted_at
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="align-top">
                                                <StatusPill
                                                    status={review.status}
                                                />
                                                <p
                                                    className={`mt-1.5 text-xs ${
                                                        hint.done
                                                            ? 'inline-flex items-center gap-1 font-medium text-primary'
                                                            : 'text-muted-foreground'
                                                    }`}
                                                >
                                                    {hint.done && (
                                                        <CheckIcon className="h-3 w-3" />
                                                    )}
                                                    {hint.label}
                                                </p>
                                            </TableCell>
                                            <TableCell className="align-top">
                                                <ScoreBars
                                                    scores={
                                                        review.section_scores
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="align-top text-secondary-foreground">
                                                {review.suggested_level ?? '—'}
                                                {review.variance_flagged && (
                                                    <p className="mt-0.5 text-[11px] text-red-600">
                                                        {
                                                            ATTENTION_LABELS.variance
                                                        }
                                                    </p>
                                                )}
                                            </TableCell>
                                            <TableCell className="align-top text-xs text-secondary-foreground">
                                                {review.assignee ?? (
                                                    <span className="text-muted-foreground">
                                                        No reviewer yet
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell className="align-top">
                                                <RowActions
                                                    review={review}
                                                    onStart={startReview}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </div>
                </>
            )}
            </GlcDataTableCard>
        </GlcLayout>
    );
}
