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
            <div className="flex min-h-screen flex-col bg-slate-50 text-slate-900">
                <header className="border-b border-slate-200 bg-white">
                    <div className="mx-auto flex h-16 max-w-3xl items-center justify-between px-4">
                        <div className="flex items-center gap-2 font-semibold">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-sm font-bold text-white">
                                GLC
                            </span>
                            <span className="text-sm sm:text-base">
                                Greats Language Center
                            </span>
                        </div>
                        <Link
                            href="/login"
                            className="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-100"
                        >
                            Log in
                        </Link>
                    </div>
                </header>

                <main className="mx-auto flex w-full max-w-3xl flex-1 flex-col justify-center gap-6 px-4 py-12">
                    <div className="text-center">
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            GLC AI Platform
                        </h1>
                        <p className="mx-auto mt-3 max-w-xl text-sm text-slate-600 sm:text-base">
                            English placement testing and a 24/7 curriculum-based
                            AI tutor for Greats Language Center students.
                        </p>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="rounded-xl border border-slate-200 bg-white p-6">
                            <h2 className="font-semibold">AI Placement Test</h2>
                            <p className="mt-2 text-sm text-slate-600">
                                Have an access code from GLC? Take your English
                                placement test on your phone or computer.
                            </p>
                            <Link
                                href="/placement"
                                className="mt-4 inline-block w-full rounded-md bg-emerald-600 px-4 py-2.5 text-center text-sm font-medium text-white hover:bg-emerald-700"
                            >
                                Enter access code
                            </Link>
                        </div>

                        <div className="rounded-xl border border-slate-200 bg-white p-6">
                            <h2 className="font-semibold">24/7 AI Tutor</h2>
                            <p className="mt-2 text-sm text-slate-600">
                                Enrolled students can practise English anytime
                                with the tutor, grounded in GLC materials.
                            </p>
                            <Link
                                href="/login"
                                className="mt-4 inline-block w-full rounded-md border border-emerald-600 px-4 py-2.5 text-center text-sm font-medium text-emerald-700 hover:bg-emerald-50"
                            >
                                Student login
                            </Link>
                        </div>
                    </div>

                    <p className="text-center text-xs text-slate-500">
                        Accounts are created by GLC staff. No self-signup is
                        available. Contact GLC for access.
                    </p>
                </main>

                <footer className="border-t border-slate-200 bg-white py-4">
                    <div className="mx-auto flex max-w-3xl flex-col items-center gap-1 px-4 text-center text-xs text-slate-400">
                        <span>
                            Greats Language Center — Kuala Lumpur, Malaysia
                        </span>
                        <span className="flex gap-3">
                            <Link
                                href="/privacy-policy"
                                className="hover:text-slate-600"
                            >
                                Privacy
                            </Link>
                            <Link
                                href="/terms-of-service"
                                className="hover:text-slate-600"
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
