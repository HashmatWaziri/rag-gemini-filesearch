import { Container } from '@/components/common/container';

export function GlcFooter() {
    const currentYear = new Date().getFullYear();

    return (
        <footer className="mt-auto border-t border-border bg-background">
            <Container className="flex flex-col items-center justify-between gap-3 py-5 md:flex-row">
                <div className="order-2 flex gap-1 text-sm md:order-1">
                    <span className="text-muted-foreground">
                        {currentYear} &copy;
                    </span>
                    <span className="font-medium text-mono">
                        Greats Language Center
                    </span>
                </div>
                <p className="order-1 text-sm text-secondary-foreground md:order-2">
                    GLC AI Platform
                </p>
            </Container>
        </footer>
    );
}
