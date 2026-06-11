import { Head } from '@inertiajs/react';

/** Friendly page for expired or unknown result links. */
export default function ResultExpired() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4 text-slate-900">
            <Head title="Link expired" />
            <div className="w-full max-w-md rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-600 text-sm font-bold text-white">
                    GLC
                </span>
                <h1 className="mt-4 text-lg font-bold">
                    This result link has expired
                </h1>
                <p className="mt-2 text-sm leading-relaxed text-slate-600">
                    Placement result links are valid for a limited time and
                    this one is no longer active, or the address is not
                    correct.
                </p>
                <p className="mt-3 text-sm leading-relaxed text-slate-600">
                    Please contact <strong>Greats Language Center</strong> and
                    our team will gladly send you a new link.
                </p>
                <p className="mt-4 text-xs text-slate-400">
                    [Contact details placeholder — pending GLC branding pack]
                </p>
            </div>
        </div>
    );
}
