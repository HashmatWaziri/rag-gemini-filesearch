import GlcLayout from '@/layouts/glc-layout';
import { Head } from '@inertiajs/react';
import { buttonPrimaryClass } from '../components';

interface Bundle {
    value: string;
    label: string;
    description: string;
    contents: string[];
}

interface RecentExport {
    id: number;
    bundle: string | null;
    actor_name: string | null;
    created_at: string;
}

interface ExportsIndexProps {
    bundles: Bundle[];
    recentExports: RecentExport[];
}

export default function ExportsIndex({
    bundles,
    recentExports,
}: ExportsIndexProps) {
    return (
        <GlcLayout title="Data Exports">
            <Head title="Exports" />

            <p className="mb-4 max-w-2xl text-sm text-slate-600">
                Download complete data bundles as ZIP archives of plain JSON and
                CSV files. GLC owns all data; bundles are usable without vendor
                lock-in. Every export is recorded in the audit log.
            </p>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                {bundles.map((bundle) => (
                    <div
                        key={bundle.value}
                        className="flex flex-col rounded-lg border border-slate-200 bg-white p-5"
                    >
                        <h2 className="text-base font-semibold text-slate-900">
                            {bundle.label}
                        </h2>
                        <p className="mt-1 text-sm text-slate-600">
                            {bundle.description}
                        </p>
                        <ul className="mt-3 space-y-1 text-xs text-slate-500">
                            {bundle.contents.map((entry) => (
                                <li key={entry} className="font-mono">
                                    {entry}
                                </li>
                            ))}
                        </ul>
                        <div className="mt-4 flex flex-1 items-end">
                            <a
                                href={`/admin/exports/${bundle.value}`}
                                className={buttonPrimaryClass}
                            >
                                Download ZIP
                            </a>
                        </div>
                    </div>
                ))}
            </div>

            <section className="mt-8">
                <h2 className="mb-3 text-base font-semibold text-slate-900">
                    Recent exports
                </h2>
                {recentExports.length === 0 ? (
                    <p className="text-sm text-slate-500">No exports yet.</p>
                ) : (
                    <ul className="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white text-sm">
                        {recentExports.map((entry) => (
                            <li
                                key={entry.id}
                                className="flex flex-wrap items-center justify-between gap-2 px-4 py-3"
                            >
                                <span className="font-medium text-slate-800">
                                    {entry.bundle ?? 'unknown'}
                                </span>
                                <span className="text-slate-500">
                                    {entry.actor_name ?? 'System'} -{' '}
                                    {new Date(
                                        entry.created_at,
                                    ).toLocaleString()}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </GlcLayout>
    );
}
