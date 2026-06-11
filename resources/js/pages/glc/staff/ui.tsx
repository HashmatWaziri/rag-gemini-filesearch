import { type ReactNode } from 'react';

/** Small shared UI helpers for the GLC staff pages (not an Inertia page). */

export function Badge({
    tone = 'slate',
    children,
}: {
    tone?: 'slate' | 'amber' | 'red' | 'emerald' | 'blue';
    children: ReactNode;
}) {
    const tones: Record<string, string> = {
        slate: 'bg-slate-100 text-slate-700',
        amber: 'bg-amber-100 text-amber-800',
        red: 'bg-red-100 text-red-700',
        emerald: 'bg-emerald-100 text-emerald-700',
        blue: 'bg-blue-100 text-blue-700',
    };

    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${tones[tone]}`}
        >
            {children}
        </span>
    );
}

export function Card({
    title,
    children,
    aside,
}: {
    title?: ReactNode;
    children: ReactNode;
    aside?: ReactNode;
}) {
    return (
        <section className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            {(title || aside) && (
                <div className="mb-3 flex items-center justify-between gap-2">
                    <h2 className="text-sm font-semibold text-slate-800">
                        {title}
                    </h2>
                    {aside}
                </div>
            )}
            {children}
        </section>
    );
}

export function Field({
    label,
    children,
    error,
}: {
    label: string;
    children: ReactNode;
    error?: string;
}) {
    return (
        <label className="block text-sm">
            <span className="mb-1 block text-xs font-medium text-slate-600">
                {label}
            </span>
            {children}
            {error && (
                <span className="mt-1 block text-xs text-red-600">{error}</span>
            )}
        </label>
    );
}

export const inputCls =
    'w-full rounded-md border border-slate-300 px-2.5 py-1.5 text-sm focus:border-emerald-500 focus:outline-none';

export const btnPrimary =
    'inline-flex items-center justify-center rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50';

export const btnSecondary =
    'inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50';

export const btnDanger =
    'inline-flex items-center justify-center rounded-md border border-red-200 bg-white px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50';

export function xsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export const SECTION_LABELS: Record<string, string> = {
    reading: 'Reading',
    grammar_vocabulary: 'Grammar & Vocabulary',
    listening: 'Listening',
    writing: 'Writing',
    speaking: 'Speaking',
};

export const SECTION_ORDER = [
    'reading',
    'grammar_vocabulary',
    'listening',
    'writing',
    'speaking',
] as const;

/** Compact mini-bar chart of section scores (0-100) for queue rows. */
export function ScoreBars({
    scores,
}: {
    scores: Record<string, number | null> | null | undefined;
}) {
    if (!scores) {
        return <span className="text-xs text-slate-300">Scores pending</span>;
    }

    return (
        <div className="flex items-end gap-1" aria-hidden={false}>
            {SECTION_ORDER.map((section) => {
                const value = scores[section];
                const pct = typeof value === 'number' ? value : null;
                const height =
                    pct === null
                        ? 2
                        : Math.max(3, Math.round((pct / 100) * 24));
                const tone =
                    pct === null
                        ? 'bg-slate-200'
                        : pct >= 70
                          ? 'bg-emerald-500'
                          : pct >= 40
                            ? 'bg-amber-400'
                            : 'bg-red-400';

                return (
                    <span
                        key={section}
                        className="flex flex-col items-center"
                        title={`${SECTION_LABELS[section]}: ${pct === null ? 'not scored yet' : `${pct}%`}`}
                        aria-label={`${SECTION_LABELS[section]}: ${pct === null ? 'not scored yet' : `${pct} percent`}`}
                    >
                        <span
                            className={`block w-1.5 rounded-sm ${tone}`}
                            style={{ height: `${height}px` }}
                        />
                    </span>
                );
            })}
        </div>
    );
}

/** Segmented 1-5 scale used for AI dimension scores. */
export function DimensionScale({
    value,
    label,
}: {
    value: number;
    label: string;
}) {
    return (
        <div
            className="flex items-center gap-0.5"
            role="img"
            aria-label={`${label}: ${value} out of 5`}
        >
            {[1, 2, 3, 4, 5].map((step) => (
                <span
                    key={step}
                    className={`h-1.5 w-4 rounded-full ${
                        step <= value
                            ? value >= 4
                                ? 'bg-emerald-500'
                                : value >= 3
                                  ? 'bg-sky-500'
                                  : 'bg-amber-400'
                            : 'bg-slate-200'
                    }`}
                />
            ))}
        </div>
    );
}

/**
 * Plain-language age of a submission, with a tone that escalates the longer
 * the test has been waiting.
 */
export function submissionAge(submittedAt: string | null): {
    label: string;
    tone: 'slate' | 'amber' | 'red';
} | null {
    if (!submittedAt) {
        return null;
    }

    const submitted = new Date(submittedAt.replace(' ', 'T'));

    if (Number.isNaN(submitted.getTime())) {
        return null;
    }

    const days = Math.floor(
        (Date.now() - submitted.getTime()) / (1000 * 60 * 60 * 24),
    );

    if (days <= 0) {
        return { label: 'Today', tone: 'slate' };
    }

    const label = days === 1 ? '1 day waiting' : `${days} days waiting`;

    return {
        label,
        tone: days >= 7 ? 'red' : days >= 3 ? 'amber' : 'slate',
    };
}

/** Checkmark glyph used in done states (SVG, no emoji). */
export function CheckIcon({
    className = 'h-3.5 w-3.5',
}: {
    className?: string;
}) {
    return (
        <svg viewBox="0 0 16 16" fill="none" aria-hidden className={className}>
            <path
                d="M3 8.5 6.5 12 13 4.5"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

/** Sparkle glyph marking AI-generated, staff-only content. */
export function SparkIcon({
    className = 'h-3.5 w-3.5',
}: {
    className?: string;
}) {
    return (
        <svg
            viewBox="0 0 16 16"
            fill="currentColor"
            aria-hidden
            className={className}
        >
            <path d="M8 1.5 9.6 6 14 7.5 9.6 9 8 13.5 6.4 9 2 7.5 6.4 6 8 1.5Z" />
        </svg>
    );
}
