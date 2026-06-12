import {
    Card,
    CardFooter,
    CardHeader,
    CardHeading,
    CardTable,
    CardToolbar,
} from '@/components/ui/card';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface GlcDataTableCardProps {
    children: ReactNode;
    filters?: ReactNode;
    actions?: ReactNode;
    footer?: ReactNode;
    className?: string;
}

export function GlcDataTableCard({
    children,
    filters,
    actions,
    footer,
    className,
}: GlcDataTableCardProps) {
    const hasHeader = filters || actions;

    return (
        <Card className={cn(className)}>
            {hasHeader && (
                <CardHeader>
                    {filters ? <CardHeading>{filters}</CardHeading> : <span />}
                    {actions ? <CardToolbar>{actions}</CardToolbar> : null}
                </CardHeader>
            )}
            <CardTable>
                <ScrollArea>
                    {children}
                    <ScrollBar orientation="horizontal" />
                </ScrollArea>
            </CardTable>
            {footer ? (
                <CardFooter className="flex-wrap justify-between gap-3">
                    {footer}
                </CardFooter>
            ) : null}
        </Card>
    );
}
