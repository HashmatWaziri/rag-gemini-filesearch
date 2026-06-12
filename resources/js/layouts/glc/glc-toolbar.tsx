import { Container } from '@/components/common/container';
import { type ReactNode } from 'react';

export function GlcToolbar({
    title,
    children,
}: {
    title?: string;
    children?: ReactNode;
}) {
    if (!title && !children) {
        return null;
    }

    return (
        <Container>
            <div className="border-t border-border" />
            <div className="my-5 flex flex-wrap items-center justify-between gap-2 lg:mb-7.5">
                {title ? (
                    <h1 className="text-lg font-semibold tracking-tight text-mono">
                        {title}
                    </h1>
                ) : (
                    <div />
                )}
                {children}
            </div>
            <div className="mb-5 border-b border-border lg:mb-7.5" />
        </Container>
    );
}
