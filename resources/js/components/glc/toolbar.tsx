import { type ReactNode } from 'react';

export function GlcPageToolbar({ children }: { children: ReactNode }) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
            {children}
        </div>
    );
}

export function GlcPageToolbarActions({ children }: { children: ReactNode }) {
    return <div className="flex items-center gap-2.5">{children}</div>;
}

export function GlcPageToolbarHeading({ children }: { children: ReactNode }) {
    return (
        <div className="flex flex-col justify-center gap-2">{children}</div>
    );
}

export function GlcPageToolbarDescription({
    children,
}: {
    children: ReactNode;
}) {
    return (
        <div className="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
            {children}
        </div>
    );
}
