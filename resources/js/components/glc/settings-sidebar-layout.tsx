import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

export interface GlcSidebarNavItem {
    id: string;
    label: string;
    active?: boolean;
}

interface GlcSettingsSidebarLayoutProps {
    items: GlcSidebarNavItem[];
    onSelect: (id: string) => void;
    children: ReactNode;
    className?: string;
}

export function GlcSettingsSidebarLayout({
    items,
    onSelect,
    children,
    className,
}: GlcSettingsSidebarLayoutProps) {
    return (
        <div className={cn('flex grow flex-col gap-5 lg:flex-row lg:gap-7.5', className)}>
            <aside className="w-full shrink-0 lg:w-[230px]">
                <nav
                    aria-label="Section navigation"
                    className="rounded-xl border border-border bg-card p-2"
                >
                    <ul className="space-y-0.5">
                        {items.map((item) => (
                            <li key={item.id}>
                                <button
                                    type="button"
                                    onClick={() => onSelect(item.id)}
                                    className={cn(
                                        'w-full rounded-md px-3 py-2 text-left text-sm font-medium transition-colors',
                                        item.active
                                            ? 'bg-primary/10 text-mono text-primary'
                                            : 'text-secondary-foreground hover:bg-accent hover:text-foreground',
                                    )}
                                >
                                    {item.label}
                                </button>
                            </li>
                        ))}
                    </ul>
                </nav>
            </aside>
            <div className="flex min-w-0 grow flex-col gap-5 lg:gap-7.5">
                {children}
            </div>
        </div>
    );
}
