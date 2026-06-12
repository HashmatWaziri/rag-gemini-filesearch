import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import GlcLayout from '@/layouts/glc-layout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { type Option } from '../components';

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
        <fieldset className="mt-3 rounded-md border border-border bg-muted/50 p-3">
            <legend className="px-1 text-xs font-medium text-secondary-foreground">
                Which documents to include
            </legend>
            <div className="space-y-1.5">
                {statuses.map((status) => (
                    <label
                        key={status.value}
                        className="flex items-start gap-2 text-xs text-secondary-foreground"
                    >
                        <input
                            type="checkbox"
                            checked={selected.includes(status.value)}
                            onChange={() => onToggle(status.value)}
                            className="mt-0.5 rounded border-input text-primary focus:ring-ring"
                        />
                        <span>{status.label}</span>
                    </label>
                ))}
            </div>
            <p className="mt-2 text-xs text-muted-foreground">
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

            <p className="mb-5 max-w-2xl text-sm text-secondary-foreground">
                Each export is a ZIP file of plain CSV and JSON files. CSV files
                open in Excel and JSON files open in any text editor, so
                everything can be read without this system — GLC always keeps
                full ownership of its data. Every download is recorded in the
                Activity Log.
            </p>

            <div className="grid grid-cols-1 gap-5 md:grid-cols-2 lg:gap-7.5">
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
                        <Card
                            key={bundle.value}
                            className="flex flex-col"
                        >
                            <CardHeader>
                                <CardTitle className="text-lg font-medium text-mono">
                                    {bundle.label}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-1 flex-col">
                                <p className="text-sm text-secondary-foreground">
                                    {bundle.description}
                                </p>
                                <ul className="mt-4 space-y-1.5 text-xs text-muted-foreground">
                                    {bundle.contents.map((entry) => (
                                        <li
                                            key={entry}
                                            className="font-mono"
                                        >
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
                            </CardContent>
                            <CardFooter className="mt-auto border-t border-border pt-5">
                                {downloadDisabled ? (
                                    <Button disabled className="w-full">
                                        Download ZIP
                                    </Button>
                                ) : (
                                    <Button asChild className="w-full">
                                        <a href={href}>Download ZIP</a>
                                    </Button>
                                )}
                            </CardFooter>
                        </Card>
                    );
                })}
            </div>

            <section className="mt-8">
                <h2 className="mb-4 text-base font-semibold text-mono">
                    Recent exports
                </h2>
                {recentExports.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Nothing has been downloaded yet.
                    </p>
                ) : (
                    <Card>
                        <CardContent className="divide-y divide-border p-0">
                            {recentExports.map((entry) => (
                                <div
                                    key={entry.id}
                                    className="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm"
                                >
                                    <span className="font-medium text-mono">
                                        {bundleLabel(entry.bundle)}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {entry.actor_name ?? 'System'} ·{' '}
                                        {new Date(
                                            entry.created_at,
                                        ).toLocaleString()}
                                    </span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </section>
        </GlcLayout>
    );
}
