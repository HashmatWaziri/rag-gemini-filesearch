import { Container } from '@/components/common/container';
import { cn } from '@/lib/utils';
import { GlcHeaderLogo } from '@/layouts/glc/glc-header-logo';
import { GlcHeaderTopbar } from '@/layouts/glc/glc-header-topbar';
import { type GlcUser } from '@/layouts/glc/nav-config';
import { useScrollPosition } from '@/hooks/use-scroll-position';
import { useEffect, useState } from 'react';

const STICKY_OFFSET = 200;

export function GlcHeader({ user }: { user: GlcUser | null }) {
    const scrollPosition = useScrollPosition();
    const [sticky, setSticky] = useState(false);

    useEffect(() => {
        setSticky(scrollPosition > STICKY_OFFSET);
    }, [scrollPosition]);

    useEffect(() => {
        if (sticky) {
            document.body.setAttribute('data-sticky-header', 'on');
        } else {
            document.body.removeAttribute('data-sticky-header');
        }
    }, [sticky]);

    return (
        <header
            className={cn(
                'flex shrink-0 items-center bg-background py-4 transition-[height,box-shadow] lg:h-[var(--header-height-default)] lg:py-0',
                sticky &&
                    'fixed inset-x-0 top-0 z-50 bg-background/70 py-3 shadow-xs backdrop-blur-md lg:h-[60px]',
            )}
        >
            <Container className="flex flex-wrap items-center gap-2 lg:gap-4">
                <GlcHeaderLogo user={user} />
                {user && <GlcHeaderTopbar user={user} />}
            </Container>
        </header>
    );
}
