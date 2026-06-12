import { Link } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon, MoreHorizontalIcon } from 'lucide-react';
import * as React from 'react';

import { Button, buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

function Pagination({ className, ...props }: React.ComponentProps<'nav'>) {
    return (
        <nav
            data-slot="pagination"
            role="navigation"
            aria-label="pagination"
            className={cn('mx-auto flex w-full justify-center', className)}
            {...props}
        />
    );
}

function PaginationContent({
    className,
    ...props
}: React.ComponentProps<'ul'>) {
    return (
        <ul
            data-slot="pagination-content"
            className={cn('flex flex-row items-center gap-1', className)}
            {...props}
        />
    );
}

function PaginationItem({ className, ...props }: React.ComponentProps<'li'>) {
    return (
        <li
            data-slot="pagination-item"
            className={cn('', className)}
            {...props}
        />
    );
}

type PaginationLinkProps = {
    isActive?: boolean;
    disabled?: boolean;
    href?: string | null;
    preserveScroll?: boolean;
    preserveState?: boolean;
} & Pick<React.ComponentProps<typeof Button>, 'size'> &
    React.ComponentProps<'a'>;

function PaginationLink({
    className,
    isActive,
    disabled,
    href,
    preserveScroll = true,
    preserveState = true,
    size = 'icon',
    children,
    ...props
}: PaginationLinkProps) {
    const classes = cn(
        buttonVariants({
            variant: isActive ? 'default' : 'outline',
            size,
        }),
        disabled && 'pointer-events-none opacity-50',
        className,
    );

    if (!href || disabled) {
        return (
            <span
                aria-current={isActive ? 'page' : undefined}
                className={classes}
                {...props}
            >
                {children}
            </span>
        );
    }

    return (
        <Link
            href={href}
            preserveScroll={preserveScroll}
            preserveState={preserveState}
            aria-current={isActive ? 'page' : undefined}
            className={classes}
            {...props}
        >
            {children}
        </Link>
    );
}

function PaginationPrevious({
    className,
    href,
    disabled,
    ...props
}: React.ComponentProps<typeof PaginationLink>) {
    return (
        <PaginationLink
            aria-label="Go to previous page"
            size="default"
            className={cn('gap-1 px-2.5 sm:pl-2.5', className)}
            href={href}
            disabled={disabled}
            {...props}
        >
            <ChevronLeftIcon className="size-4" />
            <span className="hidden sm:block">Previous</span>
        </PaginationLink>
    );
}

function PaginationNext({
    className,
    href,
    disabled,
    ...props
}: React.ComponentProps<typeof PaginationLink>) {
    return (
        <PaginationLink
            aria-label="Go to next page"
            size="default"
            className={cn('gap-1 px-2.5 sm:pr-2.5', className)}
            href={href}
            disabled={disabled}
            {...props}
        >
            <span className="hidden sm:block">Next</span>
            <ChevronRightIcon className="size-4" />
        </PaginationLink>
    );
}

function PaginationEllipsis({
    className,
    ...props
}: React.ComponentProps<'span'>) {
    return (
        <span
            data-slot="pagination-ellipsis"
            aria-hidden
            className={cn(
                'flex size-9 items-center justify-center',
                className,
            )}
            {...props}
        >
            <MoreHorizontalIcon className="size-4" />
            <span className="sr-only">More pages</span>
        </span>
    );
}

export {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
};
