import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useIsMobile } from '@/hooks/use-mobile';
import { GlcMegaMenu } from '@/layouts/glc/glc-mega-menu';
import { GlcMobileNav } from '@/layouts/glc/glc-mobile-nav';
import { type GlcUser } from '@/layouts/glc/nav-config';
import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useEffect, useState } from 'react';

export function GlcHeaderLogo({ user }: { user: GlcUser | null }) {
    const page = usePage();
    const isMobile = useIsMobile();
    const [sheetOpen, setSheetOpen] = useState(false);

    useEffect(() => {
        setSheetOpen(false);
    }, [page.url]);

    return (
        <div className="flex grow items-center gap-4 lg:gap-10">
            <div className="flex items-center gap-2.5">
                <Link
                    href={user?.role ? '/dashboard' : '/'}
                    className="flex items-center gap-2.5"
                >
                    <span className="flex size-[34px] items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                        GLC
                    </span>
                    <span className="hidden text-lg font-medium text-mono lg:inline">
                        Greats Language Center
                    </span>
                </Link>
            </div>

            {user && (
                <>
                    {!isMobile ? (
                        <GlcMegaMenu user={user} />
                    ) : (
                        <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="ms-auto"
                                    aria-label="Open navigation menu"
                                >
                                    <Menu />
                                </Button>
                            </SheetTrigger>
                            <SheetContent
                                side="left"
                                className="w-[280px] gap-0 p-0 sm:max-w-xs"
                            >
                                <SheetHeader className="border-b border-border px-4 py-4 text-left">
                                    <SheetTitle className="text-base">
                                        Navigation
                                    </SheetTitle>
                                </SheetHeader>
                                <GlcMobileNav
                                    user={user}
                                    onNavigate={() => setSheetOpen(false)}
                                />
                                <div className="mt-auto border-t border-border p-4">
                                    <Link
                                        href="/logout"
                                        method="post"
                                        as="button"
                                        className="w-full rounded-md px-3 py-2 text-left text-sm font-medium text-secondary-foreground hover:bg-accent hover:text-mono"
                                        onClick={() => setSheetOpen(false)}
                                    >
                                        Log out
                                    </Link>
                                </div>
                            </SheetContent>
                        </Sheet>
                    )}
                </>
            )}
        </div>
    );
}
