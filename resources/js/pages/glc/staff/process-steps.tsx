import {
    BadgeCheck,
    ClipboardCheck,
    FileCheck,
    ListChecks,
    Mail,
    PenLine,
    Sparkles,
    type LucideIcon,
} from 'lucide-react';
import { Badge } from './ui';

/**
 * Plain-language process pipeline for placement tests.
 *
 * The client asked for zero developer jargon in the UI and for staff to see
 * where a submission sits in the overall process, what remains, and which
 * role is responsible for each step. All display wording lives here so the
 * read-only backend enums stay untouched.
 *
 * Rendered as a Metronic Demo 7 checkout-style stepper: pill steps with
 * dashed connectors, a green check badge on completed steps and a primary
 * pill for the current step.
 */

export type ReviewStatus = 'pending' | 'in_review' | 'approved' | 'sent';

/** Human labels for the raw review status values (enum is read-only). */
export const REVIEW_STATUS_LABELS: Record<ReviewStatus, string> = {
    pending: 'Waiting for review',
    in_review: 'Being reviewed',
    approved: 'Ready to send',
    sent: 'Result sent',
};

export const REVIEW_STATUS_TONES: Record<
    ReviewStatus,
    'amber' | 'blue' | 'emerald' | 'slate'
> = {
    pending: 'amber',
    in_review: 'blue',
    approved: 'emerald',
    sent: 'slate',
};

export function reviewStatusLabel(status: string): string {
    return REVIEW_STATUS_LABELS[status as ReviewStatus] ?? status;
}

/** Plain wording for "needs attention" reasons (formerly raw flags). */
export const ATTENTION_LABELS: Record<string, string> = {
    variance: 'Uneven section results',
    integrity: 'Test-taking alert',
};

/** Plain wording for individual test-taking alerts recorded during the test. */
export const TEST_TAKING_EVENT_LABELS: Record<string, string> = {
    tab_switch: 'Left the test page during a section',
    dual_device: 'Opened the test on another device',
    paste_attempt: 'Tried to paste text into an answer',
};

export interface PipelineInput {
    status: ReviewStatus;
    submittedAt: string | null;
    hasScore: boolean;
    aiScoringDone: boolean;
    aiSuggestionsUnavailable: boolean;
    assigneeName: string | null;
    assignedToMe: boolean;
    levelConfirmed: boolean;
    summaryApprovedAt: string | null;
    finalApprovalAt: string | null;
    isMinor: boolean;
    guardianConsentConfirmed: boolean;
}

export type StepState = 'done' | 'current' | 'upcoming';

export interface PipelineStep {
    key: string;
    title: string;
    shortTitle: string;
    role: string;
    state: StepState;
    note?: string;
    caution?: string;
}

/** Compact pill labels for the horizontal stepper. */
const STEP_SHORT_TITLES: Record<string, string> = {
    submitted: 'Submitted',
    question_bank_scoring: 'Auto scoring',
    ai_provisional_scoring: 'AI scoring',
    review: 'Review & level',
    parent_summary: 'Parent summary',
    final_approval: 'Final approval',
    send_result: 'Send result',
};

const STEP_ICONS: Record<string, LucideIcon> = {
    submitted: FileCheck,
    question_bank_scoring: ListChecks,
    ai_provisional_scoring: Sparkles,
    review: ClipboardCheck,
    parent_summary: PenLine,
    final_approval: BadgeCheck,
    send_result: Mail,
};

function reviewStepNote(
    input: PipelineInput,
    reviewDone: boolean,
): string | undefined {
    if (reviewDone) {
        return 'Level confirmed';
    }

    switch (input.status) {
        case 'pending':
            return 'Waiting for a reviewer';
        case 'in_review':
            if (input.assignedToMe) {
                return 'You are reviewing';
            }
            return input.assigneeName
                ? `Being reviewed by ${input.assigneeName}`
                : 'Being reviewed';
        case 'approved':
        case 'sent':
            return undefined;
        default: {
            const exhausted: never = input.status;
            return exhausted;
        }
    }
}

/** Derives the seven placement process steps with done/current/upcoming state. */
export function derivePipeline(input: PipelineInput): PipelineStep[] {
    const sent = input.status === 'sent';
    const finalApprovalDone = input.status === 'approved' || sent;
    const reviewDone = input.levelConfirmed || finalApprovalDone;

    const steps = [
        {
            key: 'submitted',
            title: 'Test submitted',
            role: 'Candidate',
            done: true,
            note: input.submittedAt
                ? `Submitted ${input.submittedAt}`
                : undefined,
            caution: undefined as string | undefined,
        },
        {
            key: 'question_bank_scoring',
            title: 'Question-bank scoring',
            role: 'System',
            done: input.hasScore,
            note: input.hasScore
                ? 'Reading, Grammar & Vocabulary and Listening scored automatically against the question bank'
                : 'Scoring is still running — no staff action needed',
            caution: undefined as string | undefined,
        },
        {
            key: 'ai_provisional_scoring',
            title: 'AI provisional scoring',
            role: 'AI assistant · staff-only',
            done: input.aiScoringDone,
            note: input.aiScoringDone
                ? 'Writing and Speaking evaluated against the GLC guidelines — staff confirms every level'
                : sent
                  ? 'The review was completed without AI suggestions'
                  : 'AI suggestions are being prepared — the review can continue without them',
            caution: input.aiSuggestionsUnavailable
                ? 'Some AI suggestions are unavailable — review continues normally'
                : undefined,
        },
        {
            key: 'review',
            title: 'Review answers & confirm level',
            role: 'Teacher or Academic Supervisor',
            done: reviewDone,
            note: reviewStepNote(input, reviewDone),
            caution: undefined as string | undefined,
        },
        {
            key: 'parent_summary',
            title: 'Write the parent summary',
            role: 'Reviewer',
            done: input.summaryApprovedAt !== null,
            note: input.summaryApprovedAt
                ? `Summary approved ${input.summaryApprovedAt}`
                : undefined,
            caution: undefined as string | undefined,
        },
        {
            key: 'final_approval',
            title: 'Final approval',
            role: 'Teacher or Academic Supervisor',
            done: finalApprovalDone,
            note:
                finalApprovalDone && input.finalApprovalAt
                    ? `Approved ${input.finalApprovalAt}`
                    : undefined,
            caution: undefined as string | undefined,
        },
        {
            key: 'send_result',
            title: 'Send the result',
            role: 'Teacher, Academic Supervisor or Admin',
            done: sent,
            note:
                input.isMinor && input.guardianConsentConfirmed
                    ? 'Guardian consent confirmed'
                    : undefined,
            caution:
                input.isMinor && !sent && !input.guardianConsentConfirmed
                    ? 'Guardian consent must be confirmed by an Admin before sending'
                    : undefined,
        },
    ];

    // Once the result is sent the whole process is over — every step reads as
    // done even if an optional step (e.g. AI scoring) never completed.
    const firstOpen = sent ? -1 : steps.findIndex((step) => !step.done);

    return steps.map((step, index) => ({
        key: step.key,
        title: step.title,
        shortTitle: STEP_SHORT_TITLES[step.key] ?? step.title,
        role: step.role,
        state:
            sent || step.done
                ? 'done'
                : index === firstOpen
                  ? 'current'
                  : 'upcoming',
        note: step.note,
        caution: step.caution,
    }));
}

export interface NextStepInput {
    status: ReviewStatus;
    levelConfirmed: boolean;
    summaryApproved: boolean;
}

export interface SendUnlockInput {
    status: ReviewStatus;
    levelConfirmed: boolean;
    summaryApproved: boolean;
}

/** Plain-language hint for why preview/send is still locked. */
export function sendUnlockMessage(input: SendUnlockInput): string {
    const missing: string[] = [];

    if (input.status === 'pending') {
        missing.push('start the review');
    }

    if (!input.levelConfirmed) {
        missing.push('save the confirmed levels');
    }

    if (!input.summaryApproved) {
        missing.push('approve the parent summary');
    }

    if (input.status === 'in_review') {
        missing.push('give final approval');
    }

    if (missing.length === 0) {
        return 'Preview and send are not available for this review yet.';
    }

    if (missing.length === 1) {
        return `You can preview the PDF and send the result once you ${missing[0]}.`;
    }

    if (missing.length === 2) {
        return `You can preview the PDF and send the result once you ${missing[0]} and ${missing[1]}.`;
    }

    const last = missing.pop();

    return `You can preview the PDF and send the result once you ${missing.join(', ')}, and ${last}.`;
}

/** Compact "what happens next" hint for list rows, from the same pipeline logic. */
export function nextStepHint(input: NextStepInput): {
    label: string;
    done: boolean;
} {
    switch (input.status) {
        case 'pending':
            return { label: 'Next: start the review', done: false };
        case 'in_review':
            if (!input.levelConfirmed) {
                return { label: 'Next: confirm the level', done: false };
            }
            if (!input.summaryApproved) {
                return { label: 'Next: write the parent summary', done: false };
            }
            return { label: 'Next: give final approval', done: false };
        case 'approved':
            if (!input.summaryApproved) {
                return { label: 'Next: write the parent summary', done: false };
            }
            return { label: 'Next: send the result', done: false };
        case 'sent':
            return { label: 'Done — result sent', done: true };
        default: {
            const exhausted: never = input.status;
            return exhausted;
        }
    }
}

/** Green check badge pinned to the top-right corner of a completed pill. */
function StepDoneBadge() {
    return (
        <svg
            aria-hidden
            viewBox="0 0 16 16"
            fill="none"
            className="absolute -end-1 -top-1 size-4 text-green-500"
        >
            <path
                d="M15.33 8A7.33 7.33 0 1 1 .67 8a7.33 7.33 0 0 1 14.66 0Zm-7.55 2.72 4.4-4.4a.73.73 0 1 0-1.04-1.03L7.27 9.17 4.85 6.75a.73.73 0 0 0-1.04 1.03l2.93 2.94a.73.73 0 0 0 1.04 0Z"
                fill="currentColor"
            />
        </svg>
    );
}

function StepPill({ step }: { step: PipelineStep }) {
    const Icon = STEP_ICONS[step.key] ?? ClipboardCheck;

    const pillTone =
        step.state === 'current'
            ? 'border-primary/10 bg-primary/10 font-medium text-primary'
            : step.state === 'done'
              ? 'border-border bg-muted/30 text-foreground'
              : 'border-border text-foreground';

    const iconTone =
        step.state === 'current' ? 'text-primary' : 'text-muted-foreground';

    return (
        <div
            title={`${step.title} — ${step.role}`}
            aria-current={step.state === 'current' ? 'step' : undefined}
            className={`relative flex h-8.5 items-center gap-1.5 rounded-full border px-3 text-2sm leading-none whitespace-nowrap ${pillTone}`}
        >
            {step.state === 'done' && <StepDoneBadge />}
            <Icon aria-hidden className={`size-4 shrink-0 ${iconTone}`} />
            {step.shortTitle}
            <span className="sr-only">
                {step.state === 'done'
                    ? ' — completed'
                    : step.state === 'current'
                      ? ' — current step'
                      : ' — upcoming'}
            </span>
        </div>
    );
}

/**
 * The full placement process as a Metronic checkout-style stepper: pill
 * steps with dashed connectors, plus a plain-language line about the current
 * step and a collapsible who-does-what breakdown.
 */
export function ProcessSteps({ input }: { input: PipelineInput }) {
    const steps = derivePipeline(input);
    const current = steps.find((step) => step.state === 'current');
    const cautions = steps.flatMap((step) =>
        step.caution ? [step.caution] : [],
    );

    return (
        <section aria-label="Where this test is in the process">
            <ol className="flex flex-wrap items-center justify-center gap-x-2 gap-y-3 lg:flex-nowrap lg:gap-1.5">
                {steps.map((step, index) => (
                    <li key={step.key} className="flex items-center lg:gap-1.5">
                        {index > 0 && (
                            <span
                                aria-hidden
                                className="me-1.5 hidden h-px w-5 border-t border-dashed border-zinc-300 lg:block xl:w-9 dark:border-zinc-600"
                            />
                        )}
                        <StepPill step={step} />
                    </li>
                ))}
            </ol>

            <div className="mt-4 space-y-1 text-center">
                {input.status === 'sent' ? (
                    <p className="inline-flex items-center gap-2 rounded-md bg-primary/10 px-3 py-2 text-sm text-primary">
                        <svg
                            aria-hidden
                            viewBox="0 0 16 16"
                            fill="none"
                            className="size-4"
                        >
                            <path
                                d="M3 8.5 6.5 12 13 4.5"
                                stroke="currentColor"
                                strokeWidth="2"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            />
                        </svg>
                        Nothing left for you here — the result has been sent.
                    </p>
                ) : (
                    current && (
                        <p className="text-sm">
                            <span className="font-medium text-mono">
                                {current.title}
                            </span>
                            <span className="text-muted-foreground">
                                {' '}
                                · {current.role}
                            </span>
                        </p>
                    )
                )}
                {input.status !== 'sent' && current?.note && (
                    <p className="text-xs text-muted-foreground">
                        {current.note}
                    </p>
                )}
                {cautions.map((caution) => (
                    <p key={caution} className="text-xs text-amber-700">
                        {caution}
                    </p>
                ))}
            </div>

            <details className="group mt-3 text-center">
                <summary className="inline-flex cursor-pointer items-center gap-1 text-xs text-muted-foreground hover:text-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-ring/50">
                    <span className="group-open:hidden">
                        Show every step and who is responsible
                    </span>
                    <span className="hidden group-open:inline">
                        Hide step details
                    </span>
                </summary>
                <ol className="mx-auto mt-3 max-w-2xl space-y-0 rounded-lg border border-border bg-card p-4 text-start shadow-xs">
                    {steps.map((step, index) => (
                        <li
                            key={step.key}
                            className="relative flex gap-3 pb-4 last:pb-0"
                        >
                            {index < steps.length - 1 && (
                                <span
                                    aria-hidden
                                    className="absolute top-6 left-3 h-full w-px border-s border-dashed border-zinc-300 dark:border-zinc-600"
                                />
                            )}
                            <span
                                aria-hidden
                                className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold ${
                                    step.state === 'done'
                                        ? 'bg-green-500 text-white'
                                        : step.state === 'current'
                                          ? 'bg-primary text-primary-foreground ring-2 ring-primary/20'
                                          : 'bg-muted text-muted-foreground'
                                }`}
                            >
                                {step.state === 'done' ? (
                                    <svg
                                        viewBox="0 0 16 16"
                                        fill="none"
                                        className="size-3.5"
                                    >
                                        <path
                                            d="M3 8.5 6.5 12 13 4.5"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                        />
                                    </svg>
                                ) : (
                                    index + 1
                                )}
                            </span>
                            <div className="min-w-0 pt-0.5">
                                <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span
                                        className={`text-sm ${
                                            step.state === 'current'
                                                ? 'font-semibold text-foreground'
                                                : step.state === 'done'
                                                  ? 'font-medium text-secondary-foreground'
                                                  : 'text-muted-foreground'
                                        }`}
                                    >
                                        {step.title}
                                    </span>
                                    <Badge
                                        tone={
                                            step.state === 'upcoming'
                                                ? 'slate'
                                                : 'blue'
                                        }
                                    >
                                        {step.role}
                                    </Badge>
                                    {step.state === 'current' && (
                                        <Badge tone="amber">Current step</Badge>
                                    )}
                                </div>
                                {step.note && (
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        {step.note}
                                    </p>
                                )}
                                {step.caution && (
                                    <p className="mt-0.5 text-xs text-amber-700">
                                        {step.caution}
                                    </p>
                                )}
                            </div>
                        </li>
                    ))}
                </ol>
            </details>
        </section>
    );
}
