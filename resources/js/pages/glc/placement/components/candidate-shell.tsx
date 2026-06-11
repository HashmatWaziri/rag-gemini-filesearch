import { Head } from '@inertiajs/react';
import { type ReactNode } from 'react';

interface CandidateShellProps {
    title: string;
    children: ReactNode;
    wide?: boolean;
}

/**
 * Minimal mobile-first chrome for placement candidates. Deliberately not
 * GlcLayout: candidates are not authenticated users and get a focused,
 * distraction-free card layout.
 */
export default function CandidateShell({
    title,
    children,
    wide = false,
}: CandidateShellProps) {
    return (
        <div className="flex min-h-screen flex-col bg-slate-100 text-slate-900">
            <Head title={title} />
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex h-14 max-w-2xl items-center gap-2 px-4">
                    <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-sm font-bold text-white">
                        GLC
                    </span>
                    <span className="text-sm font-semibold">
                        Placement Test
                    </span>
                </div>
            </header>

            <main className="mx-auto w-full flex-1 px-4 py-6">
                <div
                    className={`mx-auto w-full ${wide ? 'max-w-2xl' : 'max-w-md'}`}
                >
                    {children}
                </div>
            </main>

            <footer className="py-4 text-center text-xs text-slate-400">
                Greats Language Center
            </footer>
        </div>
    );
}

export function Card({ children }: { children: ReactNode }) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            {children}
        </div>
    );
}

export function PrimaryButton({
    children,
    disabled = false,
    type = 'submit',
    onClick,
}: {
    children: ReactNode;
    disabled?: boolean;
    type?: 'submit' | 'button';
    onClick?: () => void;
}) {
    return (
        <button
            type={type}
            disabled={disabled}
            onClick={onClick}
            className="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
        >
            {children}
        </button>
    );
}

export function ErrorText({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-sm text-red-600">{message}</p>;
}
