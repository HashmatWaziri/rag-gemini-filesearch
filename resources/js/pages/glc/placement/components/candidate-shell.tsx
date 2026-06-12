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
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <Head title={title} />
            <header className="border-b border-border bg-background">
                <div className="mx-auto flex h-14 max-w-2xl items-center gap-2 px-4">
                    <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-sm font-bold text-primary-foreground">
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

            <footer className="py-4 text-center text-xs text-muted-foreground">
                Greats Language Center
            </footer>
        </div>
    );
}

export function Card({ children }: { children: ReactNode }) {
    return (
        <div className="rounded-xl border border-border bg-card p-5 shadow-sm">
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
            className="w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-xs transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
        >
            {children}
        </button>
    );
}

export function ErrorText({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-sm text-destructive">{message}</p>;
}
