import { Card, CardContent } from '@/components/ui/card';
import { home } from '@/routes';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="grid min-h-svh grow lg:grid-cols-2">
            <div className="order-2 flex items-center justify-center p-8 lg:order-1 lg:p-10">
                <Card className="w-full max-w-[400px] border-border shadow-sm">
                    <CardContent className="p-6">
                        <div className="mb-6 flex flex-col gap-2 text-center lg:text-left">
                            <Link
                                href={home().url}
                                className="mx-auto flex items-center gap-2.5 lg:mx-0"
                            >
                                <span className="flex size-[34px] items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                                    GLC
                                </span>
                                <span className="text-lg font-semibold text-mono">
                                    Greats Language Center
                                </span>
                            </Link>
                            {title && (
                                <h1 className="mt-4 text-xl font-semibold text-mono">
                                    {title}
                                </h1>
                            )}
                            {description && (
                                <p className="text-sm text-secondary-foreground">
                                    {description}
                                </p>
                            )}
                        </div>
                        {children}
                    </CardContent>
                </Card>
            </div>

            <div className="order-1 flex flex-col justify-center bg-linear-to-br from-primary/10 via-background to-accent p-8 lg:order-2 lg:m-5 lg:rounded-xl lg:border lg:border-border lg:p-16">
                <div className="mx-auto flex max-w-md flex-col gap-4 lg:mx-0">
                    <span className="flex size-12 items-center justify-center rounded-xl bg-primary text-lg font-bold text-primary-foreground">
                        GLC
                    </span>
                    <div className="flex flex-col gap-3">
                        <h3 className="text-2xl font-semibold text-mono">
                            GLC AI Platform
                        </h3>
                        <p className="text-base font-medium text-secondary-foreground">
                            AI English placement testing and a 24/7
                            curriculum-based tutor by{' '}
                            <span className="font-semibold text-mono">
                                Greats Language Center
                            </span>
                            .
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
