import GlcLayout from '@/layouts/glc-layout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { buttonPrimaryClass, type Option } from '../components';

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
    curriculumStatuses: Option[];
    recentExports: RecentExport[];
}

function curriculumDownloadHref(selected: string[], total: number): string {
    if (selected.length === total) {
        return '/admin/exports/curriculum';
    }

    const params = new URLSearchParams();
    selected.forEach((status) => params.append('statuses[]', status));

    return `/admin/exports/curriculum?${params.toString()}`;
}

function CurriculumStatusFilter({
    statuses,
    selected,
    onToggle,
}: {
    statuses: Option[];
    selected: string[];
    onToggle: (value: string) => void;
}) {
    return (
        <fieldset className="mt-3 rounded-md border border-slate-200 bg-slate-50 p-3">
            <legend className="px-1 text-xs font-medium text-slate-600">
                Which documents to include
            </legend>
            <div className="space-y-1.5">
                {statuses.map((status) => (
                    <label
                        key={status.value}
                        className="flex items-start gap-2 text-xs text-slate-600"
                    >
                        <input
                            type="checkbox"
                            checked={selected.includes(status.value)}
                            onChange={() => onToggle(status.value)}
                            className="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                        />
                        <span>{status.label}</span>
                    </label>
                ))}
            </div>
            <p className="mt-2 text-xs text-slate-500">
                All documents are included unless you untick a state.
            </p>
        </fieldset>
    );
}

export default function ExportsIndex({
    bundles,
    curriculumStatuses,
    recentExports,
}: ExportsIndexProps) {
    const [selectedStatuses, setSelectedStatuses] = useState<string[]>(
        curriculumStatuses.map((status) => status.value),
    );

    const toggleStatus = (value: string) => {
        setSelectedStatuses((current) =>
            current.includes(value)
                ? current.filter((status) => status !== value)
                : [...current, value],
        );
    };

    const bundleLabel = (value: string | null): string => {
        if (value === null) {
            return 'Unknown';
        }

        return bundles.find((bundle) => bundle.value === value)?.label ?? value;
    };

    return (
        <GlcLayout title="Data Exports">
            <Head title="Exports" />

            <p className="mb-4 max-w-2xl text-sm text-slate-600">
                Each export is a ZIP file of plain CSV and JSON files. CSV files
                open in Excel and JSON files open in any text editor, so
                everything can be read without this system — GLC always keeps
                full ownership of its data. Every download is recorded in the
                Activity Log.
            </p>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                {bundles.map((bundle) => {
                    const isCurriculum = bundle.value === 'curriculum';
                    const href = isCurriculum
                        ? curriculumDownloadHref(
                              selectedStatuses,
                              curriculumStatuses.length,
                          )
                        : `/admin/exports/${bundle.value}`;
                    const downloadDisabled =
                        isCurriculum && selectedStatuses.length === 0;

                    return (
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
                            {isCurriculum && (
                                <CurriculumStatusFilter
                                    statuses={curriculumStatuses}
                                    selected={selectedStatuses}
                                    onToggle={toggleStatus}
                                />
                            )}
                            <div className="mt-4 flex flex-1 items-end">
                                {downloadDisabled ? (
                                    <span
                                        className={`${buttonPrimaryClass} opacity-50`}
                                        title="Tick at least one document state to download"
                                    >
                                        Download ZIP
                                    </span>
                                ) : (
                                    <a
                                        href={href}
                                        className={buttonPrimaryClass}
                                    >
                                        Download ZIP
                                    </a>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>

            <section className="mt-8">
                <h2 className="mb-3 text-base font-semibold text-slate-900">
                    Recent exports
                </h2>
                {recentExports.length === 0 ? (
                    <p className="text-sm text-slate-500">
                        Nothing has been downloaded yet.
                    </p>
                ) : (
                    <ul className="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white text-sm">
                        {recentExports.map((entry) => (
                            <li
                                key={entry.id}
                                className="flex flex-wrap items-center justify-between gap-2 px-4 py-3"
                            >
                                <span className="font-medium text-slate-800">
                                    {bundleLabel(entry.bundle)}
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
