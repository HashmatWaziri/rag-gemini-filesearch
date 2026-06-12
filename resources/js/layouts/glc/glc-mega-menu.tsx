import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
} from '@/components/ui/navigation-menu';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import {
    getNavSectionsForRole,
    hasActiveChild,
    isNavPathActive,
    STUDENT_NAV,
    type GlcUser,
} from '@/layouts/glc/nav-config';

const linkClass = cn(
    'rounded-none border-b border-transparent bg-transparent px-0 text-sm font-medium text-secondary-foreground shadow-none',
    'hover:bg-transparent hover:text-primary focus:bg-transparent focus:text-primary',
    'data-[active=true]:border-mono data-[active=true]:bg-transparent data-[active=true]:font-semibold data-[active=true]:text-mono',
    'data-[state=open]:bg-transparent data-[state=open]:text-mono',
);

function MegaMenuPanel({
    title,
    items,
    currentPath,
}: {
    title: string;
    items: { label: string; href: string }[];
    currentPath: string;
}) {
    return (
        <div className="w-[260px] p-4">
            <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {title}
            </p>
            <ul className="space-y-1">
                {items.map((item) => {
                    const active = isNavPathActive(currentPath, item.href);

                    return (
                        <li key={item.href}>
                            <NavigationMenuLink asChild>
                                <Link
                                    href={item.href}
                                    className={cn(
                                        'block rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                        active
                                            ? 'bg-accent text-mono'
                                            : 'text-secondary-foreground hover:bg-accent hover:text-mono',
                                    )}
                                >
                                    {item.label}
                                </Link>
                            </NavigationMenuLink>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

export function GlcMegaMenu({ user }: { user: GlcUser }) {
    const page = usePage();
    const currentPath = page.url.split('?')[0] ?? page.url;
    const sections = getNavSectionsForRole(user.role);

    if (user.role === 'student') {
        return (
            <NavigationMenu viewport={false}>
                <NavigationMenuList className="gap-7.5">
                    {STUDENT_NAV.map((item) => (
                        <NavigationMenuItem key={item.href}>
                            <NavigationMenuLink asChild>
                                <Link
                                    href={item.href}
                                    className={linkClass}
                                    data-active={
                                        isNavPathActive(currentPath, item.href) ||
                                        undefined
                                    }
                                >
                                    {item.label}
                                </Link>
                            </NavigationMenuLink>
                        </NavigationMenuItem>
                    ))}
                </NavigationMenuList>
            </NavigationMenu>
        );
    }

    return (
        <NavigationMenu viewport={false}>
            <NavigationMenuList className="gap-6">
                {sections.map((section) => (
                    <NavigationMenuItem key={section.title}>
                        <NavigationMenuTrigger
                            className={linkClass}
                            data-active={
                                hasActiveChild(currentPath, section.items) ||
                                undefined
                            }
                        >
                            {section.title}
                        </NavigationMenuTrigger>
                        <NavigationMenuContent>
                            <MegaMenuPanel
                                title={section.title}
                                items={section.items}
                                currentPath={currentPath}
                            />
                        </NavigationMenuContent>
                    </NavigationMenuItem>
                ))}
            </NavigationMenuList>
        </NavigationMenu>
    );
}
