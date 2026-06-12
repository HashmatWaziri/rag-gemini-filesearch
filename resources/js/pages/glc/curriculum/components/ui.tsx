import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

import { type DocumentState } from './types';

export const inputClass =
    'flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50';

export const labelClass =
    'mb-1 block text-xs font-medium text-secondary-foreground';

export const primaryButtonClass =
    'inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50';

export const secondaryButtonClass =
    'inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-3 py-1.5 text-sm font-medium text-foreground shadow-xs hover:bg-accent disabled:cursor-not-allowed disabled:opacity-50';

export const dangerButtonClass =
    'inline-flex h-9 items-center justify-center rounded-md border border-destructive/30 bg-background px-3 py-1.5 text-sm font-medium text-destructive hover:bg-destructive/10';

const stateBadgeColors: Record<DocumentState, string> = {
    draft: 'bg-muted text-secondary-foreground',
    publishing: 'bg-secondary text-secondary-foreground',
    published: 'bg-primary/10 text-primary',
    publish_failed: 'bg-destructive/10 text-destructive',
    archived: 'bg-amber-100 text-amber-700',
};

export function stateBadgeClass(state: DocumentState): string {
    return cn(
        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap',
        stateBadgeColors[state] ?? 'bg-muted text-muted-foreground',
    );
}

export { Button, Input, Label };
