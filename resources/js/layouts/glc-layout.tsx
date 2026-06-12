import { Container } from '@/components/common/container';
import { GlcFooter } from '@/layouts/glc/glc-footer';
import { GlcHeader } from '@/layouts/glc/glc-header';
import { GlcToolbar } from '@/layouts/glc/glc-toolbar';
import { type GlcUser } from '@/layouts/glc/nav-config';
import { useBodyClass } from '@/hooks/use-body-class';
import { usePage } from '@inertiajs/react';
import { type ReactNode } from 'react';

interface GlcLayoutProps {
    children: ReactNode;
    title?: string;
}

/**
 * Metronic Demo 7-style shell for GLC platform pages (staff, student, admin).
 * Placement candidate pages use their own minimal chrome instead.
 */
export default function GlcLayout({ children, title }: GlcLayoutProps) {
    const page = usePage<{ auth: { user: GlcUser | null } }>();
    const user = page.props.auth?.user ?? null;

    useBodyClass(`
        [--header-height-default:95px]
        data-[sticky-header=on]:[--header-height:60px]
        [--header-height:var(--header-height-default)]
        [--header-height-mobile:70px]
    `);

    return (
        <div className="flex min-h-screen flex-col bg-background text-foreground">
            <GlcHeader user={user} />

            <div className="flex flex-1 flex-col in-data-[sticky-header=on]:pt-[var(--header-height-default)]">
                <GlcToolbar title={title} />

                <Container className="flex-1 pb-6">{children}</Container>
            </div>

            <GlcFooter />
        </div>
    );
}
