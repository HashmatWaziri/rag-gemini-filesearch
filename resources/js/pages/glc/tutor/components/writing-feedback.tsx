import { useMemo } from 'react';

export interface Highlight {
    start: number;
    end: number;
    type: string;
    comment: string;
}

export interface DimensionFeedback {
    score: number;
    comment: string;
}

export interface SubmissionPayload {
    id: number;
    status: string;
    text: string;
    feedback: {
        dimensions: Record<string, DimensionFeedback>;
        summary: string;
    } | null;
    highlights: Highlight[];
    error: string | null;
    created_at: string | null;
}

export const DIMENSIONS: Record<
    string,
    { label: string; mark: string; dot: string }
> = {
    grammar: {
        label: 'Grammar',
        mark: 'bg-red-100 decoration-red-400',
        dot: 'bg-red-400',
    },
    vocabulary: {
        label: 'Vocabulary',
        mark: 'bg-amber-100 decoration-amber-400',
        dot: 'bg-amber-400',
    },
    structure: {
        label: 'Structure',
        mark: 'bg-sky-100 decoration-sky-400',
        dot: 'bg-sky-400',
    },
    coherence: {
        label: 'Coherence',
        mark: 'bg-violet-100 decoration-violet-400',
        dot: 'bg-violet-400',
    },
    task_completion: {
        label: 'Task Completion',
        mark: 'bg-primary/10 decoration-primary',
        dot: 'bg-primary',
    },
};

function ScoreDots({ score }: { score: number }) {
    return (
        <span
            className="flex items-center gap-1"
            aria-label={`Score ${score} out of 5`}
        >
            {[1, 2, 3, 4, 5].map((step) => (
                <span
                    key={step}
                    className={`h-2.5 w-2.5 rounded-full ${
                        step <= score ? 'bg-primary' : 'bg-muted'
                    }`}
                />
            ))}
        </span>
    );
}

interface Segment {
    text: string;
    highlight: Highlight | null;
}

function buildSegments(text: string, highlights: Highlight[]): Segment[] {
    const sorted = [...highlights].sort((a, b) => a.start - b.start);
    const segments: Segment[] = [];
    let cursor = 0;

    for (const highlight of sorted) {
        if (
            highlight.start < cursor ||
            highlight.end <= highlight.start ||
            highlight.end > text.length
        ) {
            continue;
        }

        if (highlight.start > cursor) {
            segments.push({
                text: text.slice(cursor, highlight.start),
                highlight: null,
            });
        }

        segments.push({
            text: text.slice(highlight.start, highlight.end),
            highlight,
        });
        cursor = highlight.end;
    }

    if (cursor < text.length) {
        segments.push({ text: text.slice(cursor), highlight: null });
    }

    return segments;
}

function HighlightedText({
    text,
    highlights,
}: {
    text: string;
    highlights: Highlight[];
}) {
    const segments = useMemo(
        () => buildSegments(text, highlights),
        [text, highlights],
    );

    return (
        <p className="text-sm leading-7 whitespace-pre-wrap text-foreground">
            {segments.map((segment, index) =>
                segment.highlight ? (
                    <mark
                        key={index}
                        title={`${DIMENSIONS[segment.highlight.type]?.label ?? segment.highlight.type}: ${segment.highlight.comment}`}
                        className={`cursor-help rounded px-0.5 underline decoration-2 underline-offset-4 ${
                            DIMENSIONS[segment.highlight.type]?.mark ??
                            'bg-muted'
                        }`}
                    >
                        {segment.text}
                    </mark>
                ) : (
                    <span key={index}>{segment.text}</span>
                ),
            )}
        </p>
    );
}

/**
 * Renders a completed writing correction: highlighted text, legend, the
 * five 1-5 dimension scores, comments, and the overall summary. No letter
 * grade and no IELTS band, per Phase 1 contract.
 */
export default function WritingFeedback({
    submission,
}: {
    submission: SubmissionPayload;
}) {
    if (submission.status === 'failed') {
        return (
            <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {submission.error ??
                    'We could not evaluate this submission. Please try again later.'}
            </div>
        );
    }

    if (submission.status !== 'completed' || !submission.feedback) {
        return (
            <div className="rounded-lg border border-border bg-card p-4 text-sm text-muted-foreground">
                This writing is still being checked. Please refresh in a moment.
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <section>
                <h3 className="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                    Your text with highlights
                </h3>
                <div className="rounded-lg border border-border bg-card p-4">
                    <HighlightedText
                        text={submission.text}
                        highlights={submission.highlights}
                    />
                </div>
                <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                    {Object.entries(DIMENSIONS).map(([key, meta]) => (
                        <span
                            key={key}
                            className="flex items-center gap-1.5 text-xs text-secondary-foreground"
                        >
                            <span
                                className={`h-2.5 w-2.5 rounded-full ${meta.dot}`}
                            />
                            {meta.label}
                        </span>
                    ))}
                </div>
                {submission.highlights.length > 0 && (
                    <ul className="mt-3 space-y-1.5">
                        {submission.highlights.map((highlight, index) => (
                            <li
                                key={index}
                                className="flex items-start gap-2 text-xs text-secondary-foreground"
                            >
                                <span
                                    className={`mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full ${
                                        DIMENSIONS[highlight.type]?.dot ??
                                        'bg-muted-foreground/50'
                                    }`}
                                />
                                <span>
                                    <span className="font-medium text-foreground">
                                        &quot;
                                        {submission.text.slice(
                                            highlight.start,
                                            highlight.end,
                                        )}
                                        &quot;
                                    </span>{' '}
                                    {highlight.comment}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <section>
                <h3 className="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                    Feedback by area
                </h3>
                <ul className="divide-y divide-border overflow-hidden rounded-lg border border-border bg-card">
                    {Object.entries(DIMENSIONS).map(([key, meta]) => {
                        const feedback = submission.feedback?.dimensions[key];

                        if (!feedback) {
                            return null;
                        }

                        return (
                            <li key={key} className="px-4 py-3">
                                <div className="flex items-center justify-between gap-3">
                                    <span className="text-sm font-medium text-foreground">
                                        {meta.label}
                                    </span>
                                    <ScoreDots score={feedback.score} />
                                </div>
                                {feedback.comment && (
                                    <p className="mt-1 text-sm text-secondary-foreground">
                                        {feedback.comment}
                                    </p>
                                )}
                            </li>
                        );
                    })}
                </ul>
            </section>

            {submission.feedback.summary && (
                <section>
                    <h3 className="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Overall summary
                    </h3>
                    <p className="rounded-lg border border-primary/20 bg-primary/5 p-4 text-sm text-primary">
                        {submission.feedback.summary}
                    </p>
                </section>
            )}
        </div>
    );
}
