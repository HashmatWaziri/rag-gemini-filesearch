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
            {error && <span className="mt-1 block text-xs text-red-600">{error}</span>}
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
