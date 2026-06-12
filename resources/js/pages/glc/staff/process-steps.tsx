import { Badge } from './ui';

/**
 * Plain-language process pipeline for placement tests.
 *
 * The client asked for zero developer jargon in the UI and for staff to see
 * where a submission sits in the overall process, what remains, and which
 * role is responsible for each step. All display wording lives here so the
 * read-only backend enums stay untouched.
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
    role: string;
    state: StepState;
    note?: string;
    caution?: string;
}

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

    const firstOpen = steps.findIndex((step) => !step.done);

    return steps.map((step, index) => ({
        key: step.key,
        title: step.title,
        role: step.role,
        state: step.done
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

function StepMarker({
    state,
    position,
}: {
    state: StepState;
    position: number;
}) {
    if (state === 'done') {
        return (
            <span
                aria-hidden
                className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground"
            >
                ✓
            </span>
        );
    }

    return (
        <span
            aria-hidden
            className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold ${
                state === 'current'
                    ? 'bg-primary text-primary-foreground ring-2 ring-primary/20'
                    : 'bg-muted text-muted-foreground'
            }`}
        >
            {position}
        </span>
    );
}

/**
 * The full placement process, step by step, with the responsible role for
 * each step. Rendered prominently on the review page.
 */
export function ProcessSteps({ input }: { input: PipelineInput }) {
    const steps = derivePipeline(input);

    return (
        <section className="rounded-lg border border-border bg-card p-4 shadow-sm">
            <h2 className="text-sm font-semibold text-mono">
                Where this test is in the process
            </h2>
            <p className="mt-0.5 text-xs text-muted-foreground">
                Each step shows who is responsible for it.
            </p>

            <ol className="mt-3">
                {steps.map((step, index) => (
                    <li
                        key={step.key}
                        className="relative flex gap-3 pb-4 last:pb-0"
                    >
                        {index < steps.length - 1 && (
                            <span
                                aria-hidden
                                className="absolute top-6 left-3 h-full w-px bg-border"
                            />
                        )}
                        <StepMarker state={step.state} position={index + 1} />
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

            {input.status === 'sent' && (
                <p className="mt-3 flex items-center gap-2 rounded-md bg-primary/10 px-3 py-2 text-sm text-primary">
                    <span aria-hidden className="font-bold">
                        ✓
                    </span>
                    Nothing left for you here — the result has been sent.
                </p>
            )}
        </section>
    );
}
