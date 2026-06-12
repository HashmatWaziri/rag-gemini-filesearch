import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge as UiBadge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Pagination as UiPagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';
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
    'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50';

export const buttonPrimaryClass =
    'inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50';

export const buttonSecondaryClass =
    'inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium text-foreground shadow-xs hover:bg-accent disabled:cursor-not-allowed disabled:opacity-50';

export const buttonDangerClass =
    'inline-flex h-9 items-center justify-center rounded-md bg-destructive px-4 py-2 text-sm font-medium text-white shadow-xs hover:bg-destructive/90 disabled:cursor-not-allowed disabled:opacity-50';

export function StatusBanner({ message }: { message?: string | null }) {
    if (!message) {
        return null;
    }

    return (
        <Alert className="mb-4 border-primary/20 bg-primary/5 text-primary">
            <AlertDescription>{message}</AlertDescription>
        </Alert>
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
        <div className="space-y-1.5">
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            {hint && (
                <p className="text-xs text-muted-foreground">{hint}</p>
            )}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}

type BadgeTone = 'green' | 'amber' | 'red' | 'slate' | 'blue';

const BADGE_VARIANTS: Record<
    BadgeTone,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    green: 'default',
    amber: 'secondary',
    red: 'destructive',
    slate: 'outline',
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

export function Pagination({ paginator }: { paginator: Paginator<unknown> }) {
    if (paginator.total === 0) {
        return null;
    }

    return (
        <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-secondary-foreground">
                Showing {paginator.from ?? 0}–{paginator.to ?? 0} of{' '}
                {paginator.total}
            </p>
            <UiPagination className="mx-0 w-auto justify-end">
                <PaginationContent>
                    <PaginationItem>
                        <PaginationPrevious
                            href={paginator.prev_page_url}
                            disabled={!paginator.prev_page_url}
                        />
                    </PaginationItem>
                    <PaginationItem>
                        <PaginationNext
                            href={paginator.next_page_url}
                            disabled={!paginator.next_page_url}
                        />
                    </PaginationItem>
                </PaginationContent>
            </UiPagination>
        </div>
    );
}

export interface PaginationLinkItem {
    url: string | null;
    label: string;
    active: boolean;
}

export interface LinkPaginator {
    links: PaginationLinkItem[];
    total: number;
}

export function LinkPagination({
    paginator,
    className,
}: {
    paginator: LinkPaginator;
    className?: string;
}) {
    if (paginator.total === 0) {
        return null;
    }

    return (
        <div className={cn('flex flex-wrap items-center gap-1', className)}>
            <UiPagination className="mx-0 w-auto justify-start">
                <PaginationContent>
                    {paginator.links.map((link, index) => (
                        <PaginationItem key={`${link.label}-${index}`}>
                            {link.url ? (
                                <Link
                                    href={link.url}
                                    preserveScroll
                                    className={cn(
                                        buttonVariants({
                                            variant: link.active
                                                ? 'default'
                                                : 'outline',
                                            size: 'icon',
                                        }),
                                        'min-w-9',
                                    )}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : (
                                <span
                                    className={cn(
                                        buttonVariants({
                                            variant: 'outline',
                                            size: 'icon',
                                        }),
                                        'min-w-9 pointer-events-none opacity-50',
                                    )}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            )}
                        </PaginationItem>
                    ))}
                </PaginationContent>
            </UiPagination>
            <span className="ml-auto text-xs text-muted-foreground">
                {paginator.total} total
            </span>
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
    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                </DialogHeader>
                {children}
            </DialogContent>
        </Dialog>
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
    return (
        <Dialog open={open} onOpenChange={(next) => !next && onCancel()}>
            <DialogContent className="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{message}</DialogDescription>
                </DialogHeader>
                <DialogFooter className="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onCancel}
                        disabled={processing}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant={danger ? 'destructive' : 'default'}
                        onClick={onConfirm}
                        disabled={processing}
                    >
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export function PrivacyNoticeSection({ text }: { text: string }) {
    return (
        <section className="rounded-lg border border-border bg-muted/50 p-4">
            <h3 className="text-sm font-semibold text-mono">
                Privacy Notice (PDPA)
            </h3>
            <p className="mt-2 text-xs leading-relaxed text-secondary-foreground">
                {text}
            </p>
        </section>
    );
}

// Re-export Input for pages that import it from here
export { Input };
