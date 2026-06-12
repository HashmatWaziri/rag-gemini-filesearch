import { Badge as UiBadge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card as UiCard,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

/** Small shared UI helpers for the GLC staff pages (not an Inertia page). */

type BadgeTone = 'slate' | 'amber' | 'red' | 'emerald' | 'blue' | 'green';

const BADGE_VARIANTS: Record<
    BadgeTone,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    slate: 'outline',
    amber: 'secondary',
    red: 'destructive',
    emerald: 'default',
    green: 'default',
    blue: 'default',
};

export function Badge({
    tone = 'slate',
    children,
}: {
    tone?: BadgeTone;
    children: ReactNode;
}) {
    return (
        <UiBadge variant={BADGE_VARIANTS[tone]} className="whitespace-nowrap">
            {children}
        </UiBadge>
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
        <UiCard className="py-4">
            {(title || aside) && (
                <CardHeader className="flex-row items-center justify-between space-y-0 pb-3">
                    {title ? (
                        <CardTitle className="text-sm font-semibold text-mono">
                            {title}
                        </CardTitle>
                    ) : (
                        <div />
                    )}
                    {aside}
                </CardHeader>
            )}
            <CardContent className={title || aside ? undefined : 'pt-0'}>
                {children}
            </CardContent>
        </UiCard>
    );
}

export function Field({
    label,
    children,
    error,
    htmlFor,
}: {
    label: string;
    children: ReactNode;
    error?: string;
    htmlFor?: string;
}) {
    return (
        <div className="space-y-1.5 text-sm">
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            {error && (
                <p className="text-xs text-destructive">{error}</p>
            )}
        </div>
    );
}

export const inputCls =
    'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50';

export const btnPrimary =
    'inline-flex h-9 items-center justify-center rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90 disabled:opacity-50';

export const btnSecondary =
    'inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium text-foreground shadow-xs hover:bg-accent disabled:opacity-50';

export const btnDanger =
    'inline-flex h-9 items-center justify-center rounded-md bg-destructive px-2.5 py-1 text-xs font-medium text-white hover:bg-destructive/90';

export { Button, Input };

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
        return (
            <span className="text-xs text-muted-foreground/50">
                Scores pending
            </span>
        );
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
                        ? 'bg-muted'
                        : pct >= 70
                          ? 'bg-primary'
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
                            className={cn('block w-1.5 rounded-sm', tone)}
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
                    className={cn(
                        'h-1.5 w-4 rounded-full',
                        step <= value
                            ? value >= 4
                                ? 'bg-primary'
                                : value >= 3
                                  ? 'bg-primary/70'
                                  : 'bg-amber-400'
                            : 'bg-muted',
                    )}
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
