import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import {
    getFlatNavItems,
    getNavSectionsForRole,
    isNavPathActive,
    type GlcUser,
} from '@/layouts/glc/nav-config';

function MobileNavLink({
    item,
    currentPath,
    onNavigate,
}: {
    item: { label: string; href: string };
    currentPath: string;
    onNavigate: () => void;
}) {
    const active = isNavPathActive(currentPath, item.href);

    return (
        <Link
            href={item.href}
            onClick={onNavigate}
            className={cn(
                'block rounded-md px-3 py-2 text-sm font-medium transition-colors',
                active
                    ? 'bg-accent text-mono'
                    : 'text-secondary-foreground hover:bg-accent hover:text-mono',
            )}
        >
            {item.label}
        </Link>
    );
}

export function GlcMobileNav({
    user,
    onNavigate,
}: {
    user: GlcUser;
    onNavigate: () => void;
}) {
    const page = usePage();
    const currentPath = page.url.split('?')[0] ?? page.url;
    const sections = getNavSectionsForRole(user.role);
    const flatItems = getFlatNavItems(user.role);

    if (user.role === 'student') {
        return (
            <nav className="flex flex-col gap-1 p-4">
                {flatItems.map((item) => (
                    <MobileNavLink
                        key={item.href}
                        item={item}
                        currentPath={currentPath}
                        onNavigate={onNavigate}
                    />
                ))}
            </nav>
        );
    }

    return (
        <nav className="flex flex-col gap-2 p-4">
            {sections.map((section) => (
                <Collapsible key={section.title} defaultOpen>
                    <CollapsibleTrigger className="group flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-semibold text-mono hover:bg-accent">
                        {section.title}
                        <ChevronDown className="size-4 text-muted-foreground transition-transform group-data-[state=open]:rotate-180" />
                    </CollapsibleTrigger>
                    <CollapsibleContent className="space-y-1 ps-2 pt-1">
                        {section.items.map((item) => (
                            <MobileNavLink
                                key={item.href}
                                item={item}
                                currentPath={currentPath}
                                onNavigate={onNavigate}
                            />
                        ))}
                    </CollapsibleContent>
                </Collapsible>
            ))}
        </nav>
    );
}
