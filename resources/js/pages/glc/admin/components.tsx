import { Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

export interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export interface Option {
    value: string;
    label: string;
}

export const inputClass =
    'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500';

export const buttonPrimaryClass =
    'inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50';

export const buttonSecondaryClass =
    'inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50';

export const buttonDangerClass =
    'inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-50';

export function StatusBanner({ message }: { message?: string | null }) {
    if (!message) {
        return null;
    }

    return (
        <div className="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {message}
        </div>
    );
}

interface FieldProps {
    label: string;
    htmlFor?: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}

export function Field({ label, htmlFor, error, hint, children }: FieldProps) {
    return (
        <div className="space-y-1">
            <label
                htmlFor={htmlFor}
                className="block text-sm font-medium text-slate-700"
            >
                {label}
            </label>
            {children}
            {hint && <p className="text-xs text-slate-500">{hint}</p>}
            {error && <p className="text-xs text-red-600">{error}</p>}
        </div>
    );
}

type BadgeTone = 'green' | 'amber' | 'red' | 'slate' | 'blue';

const BADGE_TONES: Record<BadgeTone, string> = {
    green: 'bg-emerald-100 text-emerald-800',
    amber: 'bg-amber-100 text-amber-800',
    red: 'bg-red-100 text-red-800',
    slate: 'bg-slate-100 text-slate-700',
    blue: 'bg-blue-100 text-blue-800',
};

export function Badge({
    tone = 'slate',
    children,
}: {
    tone?: BadgeTone;
    children: ReactNode;
}) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ${BADGE_TONES[tone]}`}
        >
            {children}
        </span>
    );
}

export function Pagination({ paginator }: { paginator: Paginator<unknown> }) {
    if (paginator.total === 0) {
        return null;
    }

    return (
        <div className="mt-4 flex items-center justify-between gap-3 text-sm text-slate-600">
            <p>
                Showing {paginator.from ?? 0}-{paginator.to ?? 0} of{' '}
                {paginator.total}
            </p>
            <div className="flex gap-2">
                {paginator.prev_page_url ? (
                    <Link
                        href={paginator.prev_page_url}
                        preserveScroll
                        preserveState
                        className={buttonSecondaryClass}
                    >
                        Previous
                    </Link>
                ) : (
                    <span className={`${buttonSecondaryClass} opacity-50`}>
                        Previous
                    </span>
                )}
                {paginator.next_page_url ? (
                    <Link
                        href={paginator.next_page_url}
                        preserveScroll
                        preserveState
                        className={buttonSecondaryClass}
                    >
                        Next
                    </Link>
                ) : (
                    <span className={`${buttonSecondaryClass} opacity-50`}>
                        Next
                    </span>
                )}
            </div>
        </div>
    );
}

interface ModalProps {
    open: boolean;
    title: string;
    onClose: () => void;
    children: ReactNode;
}

export function Modal({ open, title, onClose, children }: ModalProps) {
    if (!open) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 p-0 sm:items-center sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-label={title}
        >
            <div className="max-h-[90vh] w-full overflow-y-auto rounded-t-xl bg-white p-5 shadow-xl sm:max-w-lg sm:rounded-xl">
                <div className="mb-4 flex items-start justify-between gap-3">
                    <h2 className="text-lg font-semibold text-slate-900">
                        {title}
                    </h2>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md px-2 py-1 text-sm text-slate-500 hover:bg-slate-100"
                        aria-label="Close"
                    >
                        Close
                    </button>
                </div>
                {children}
            </div>
        </div>
    );
}

interface ConfirmDialogProps {
    open: boolean;
    title: string;
    message: string;
    confirmLabel: string;
    danger?: boolean;
    processing?: boolean;
    onConfirm: () => void;
    onCancel: () => void;
}

export function ConfirmDialog({
    open,
    title,
    message,
    confirmLabel,
    danger = false,
    processing = false,
    onConfirm,
    onCancel,
}: ConfirmDialogProps) {
    if (!open) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            role="alertdialog"
            aria-modal="true"
            aria-label={title}
        >
            <div className="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl">
                <h2 className="text-base font-semibold text-slate-900">
                    {title}
                </h2>
                <p className="mt-2 text-sm text-slate-600">{message}</p>
                <div className="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        onClick={onCancel}
                        disabled={processing}
                        className={buttonSecondaryClass}
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        onClick={onConfirm}
                        disabled={processing}
                        className={
                            danger ? buttonDangerClass : buttonPrimaryClass
                        }
                    >
                        {confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}

export function PrivacyNoticeSection({ text }: { text: string }) {
    return (
        <section className="rounded-md border border-slate-200 bg-slate-50 p-4">
            <h3 className="text-sm font-semibold text-slate-800">
                Privacy Notice (PDPA)
            </h3>
            <p className="mt-2 text-xs leading-relaxed text-slate-600">
                {text}
            </p>
        </section>
    );
}
