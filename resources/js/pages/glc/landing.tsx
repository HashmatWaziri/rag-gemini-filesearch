import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link } from '@inertiajs/react';

export default function GlcLanding() {
    return (
        <>
            <Head title="Welcome">
                <meta
                    name="description"
                    content="GLC AI Platform - AI Placement Test and 24/7 AI Tutor for Greats Language Center students."
                />
            </Head>
            <div className="flex min-h-screen flex-col bg-background text-foreground">
                <header className="border-b border-border bg-background">
                    <div className="mx-auto flex h-16 max-w-3xl items-center justify-between px-4">
                        <div className="flex items-center gap-2 font-semibold">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-sm font-bold text-primary-foreground">
                                GLC
                            </span>
                            <span className="text-sm sm:text-base">
                                Greats Language Center
                            </span>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href="/login">Log in</Link>
                        </Button>
                    </div>
                </header>

                <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col justify-center gap-6 px-4 py-12">
                    <div className="text-center">
                        <h1 className="text-2xl font-semibold tracking-tight text-mono sm:text-3xl">
                            GLC AI Platform
                        </h1>
                        <p className="mx-auto mt-3 max-w-xl text-sm text-muted-foreground sm:text-base">
                            English placement testing and a 24/7 curriculum-based
                            AI tutor for Greats Language Center students.
                        </p>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>AI Placement Test</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    Have an access code from GLC? Take your English
                                    placement test on your phone or computer.
                                </p>
                            </CardContent>
                            <CardFooter>
                                <Button className="w-full" asChild>
                                    <Link href="/placement">
                                        Enter access code
                                    </Link>
                                </Button>
                            </CardFooter>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>24/7 AI Tutor</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-sm text-muted-foreground">
                                    Enrolled students can practise English anytime
                                    with the tutor, grounded in GLC materials.
                                </p>
                            </CardContent>
                            <CardFooter>
                                <Button variant="outline" className="w-full" asChild>
                                    <Link href="/login">Student login</Link>
                                </Button>
                            </CardFooter>
                        </Card>
                    </div>

                    <p className="text-center text-xs text-muted-foreground">
                        Accounts are created by GLC staff. No self-signup is
                        available. Contact GLC for access.
                    </p>
                </main>

                <footer className="border-t border-border bg-background py-4">
                    <div className="mx-auto flex max-w-3xl flex-col items-center gap-1 px-4 text-center text-xs text-muted-foreground">
                        <span>
                            Greats Language Center — Kuala Lumpur, Malaysia
                        </span>
                        <span className="flex gap-3">
                            <Link
                                href="/privacy-policy"
                                className="hover:text-foreground"
                            >
                                Privacy
                            </Link>
                            <Link
                                href="/terms-of-service"
                                className="hover:text-foreground"
                            >
                                Terms
                            </Link>
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
