import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import {
    type DocumentState,
} from './components/types';
import {
    dangerButtonClass,
    inputClass,
    primaryButtonClass,
    secondaryButtonClass,
    stateBadgeClass,
} from './components/ui';

interface DocumentVersion {
    version: number;
    original_filename: string;
    published_at: string | null;
    created_at: string | null;
    can_restore: boolean;
}

interface DocumentDetail {
    id: number;
    title: string;
    material_kind: string;
    material_kind_label: string;
    course: string;
    level: string;
    unit: string;
    lesson: string | null;
    format: string;
    original_filename: string;
    status: string;
    index_status: string;
    index_error: string | null;
    state: DocumentState;
    state_label: string;
    version: number;
    has_stored_file: boolean;
    file_size_label: string | null;
    uploaded_by: string | null;
    published_at: string | null;
    archived_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

interface CurriculumShowProps {
    document: DocumentDetail;
    versions: DocumentVersion[];
    canDelete: boolean;
    canPublish: boolean;
    canReplace: boolean;
    canArchive: boolean;
    canReindex: boolean;
    status: string | null;
}

export default function CurriculumShow({
    document,
    versions,
    canDelete,
    canPublish,
    canReplace,
    canArchive,
    canReindex,
    status,
}: CurriculumShowProps) {
    const { errors } = usePage().props as { errors: Record<string, string> };

    const publishForm = useForm({ preview_confirmed: false });
    const replaceForm = useForm<{ file: File | null }>({ file: null });
    const [showReplace, setShowReplace] = useState(false);

    const baseUrl = `/staff/curriculum/documents/${document.id}`;
    const fileReadyToPublish = document.has_stored_file;

    const submitPublish = (e: FormEvent) => {
        e.preventDefault();
        publishForm.post(`${baseUrl}/publish`, { preserveScroll: true });
    };

    const submitReplace = (e: FormEvent) => {
        e.preventDefault();
        replaceForm.post(`${baseUrl}/replace`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                replaceForm.reset();
                setShowReplace(false);
            },
        });
    };

    const archive = () => {
        if (
            confirm(
                'Archive this document? The AI Tutor will stop using it with students. The file and its details stay saved, and you can bring it back later by uploading a new version.',
            )
        ) {
            router.post(`${baseUrl}/archive`, {}, { preserveScroll: true });
        }
    };

    const tryAgain = () =>
        router.post(`${baseUrl}/reindex`, {}, { preserveScroll: true });

    const destroy = () => {
        if (
            confirm(
                'Permanently delete this document? It will be removed for everyone — staff, students, and the AI Tutor — along with its file. This cannot be undone.',
            )
        ) {
            router.delete(baseUrl);
        }
    };

    const restoreVersion = (version: number) => {
        if (
            confirm(
                `Restore version ${version}? A new draft version will be created from that file. If a published version is live for students, it stays available until you publish the restored one.`,
            )
        ) {
            router.post(
                `${baseUrl}/versions/${version}/restore`,
                {},
                { preserveScroll: true },
            );
        }
    };

    const metadata: [string, string][] = [
        ['Course', document.course],
        ['Level', document.level],
        ['Unit', document.unit],
        ['Lesson', document.lesson ?? 'Whole unit'],
        ['Material type', document.material_kind_label],
        ['Format', document.format.toUpperCase()],
        ['Original filename', document.original_filename],
        ['File size', document.file_size_label ?? 'Unknown'],
        ['Version', `v${document.version}`],
        ['Uploaded by', document.uploaded_by ?? 'Unknown'],
        ['Uploaded at', document.created_at ?? '-'],
        ['Last updated', document.updated_at ?? '-'],
        ['Published at', document.published_at ?? '-'],
        ['Archived at', document.archived_at ?? '-'],
    ];

    const canTryAgain =
        document.state === 'publish_failed' ||
        (document.state === 'publishing' &&
            document.index_status === 'pending');

    return (
        <GlcLayout title={document.title}>
            <Head title={document.title} />

            <div className="space-y-6">
                <Link
                    href="/staff/curriculum"
                    className="text-sm font-medium text-primary hover:underline"
                >
                    Back to curriculum
                </Link>

                {status && (
                    <div className="rounded-md border border-primary/20 bg-primary/10 px-4 py-3 text-sm text-primary">
                        {status}
                    </div>
                )}

                {errors.status && (
                    <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {errors.status}
                    </div>
                )}

                <section className="rounded-lg border border-border bg-card p-4">
                    <div className="mb-3 flex flex-wrap items-center gap-2">
                        <span className={stateBadgeClass(document.state)}>
                            {document.state_label}
                        </span>
                    </div>

                    {document.state === 'publish_failed' && (
                        <div className="mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                            <p>
                                Something went wrong while uploading this file
                                to the AI Tutor. Use "Try again" below — if it
                                keeps failing, contact support.
                            </p>
                            {document.index_error && (
                                <details className="mt-1">
                                    <summary className="cursor-pointer font-medium">
                                        Technical details
                                    </summary>
                                    <p className="mt-1 break-words">
                                        {document.index_error}
                                    </p>
                                </details>
                            )}
                        </div>
                    )}

                    {document.state === 'publishing' && (
                        <p className="mb-3 text-xs text-muted-foreground">
                            This file is being uploaded to the AI Tutor File
                            Search store. This usually takes a few minutes —
                            refresh the page to see the latest status.
                        </p>
                    )}

                    <dl className="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
                        {metadata.map(([label, value]) => (
                            <div key={label}>
                                <dt className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                    {label}
                                </dt>
                                <dd className="text-sm text-secondary-foreground">
                                    {value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </section>

                <section className="rounded-lg border border-border bg-card p-4">
                    <h2 className="mb-2 text-sm font-semibold text-mono">
                        File for the AI Tutor
                    </h2>
                    <p className="mb-3 text-xs text-muted-foreground">
                        Publishing uploads this file to the Gemini File Search
                        store. Gemini reads and indexes the document directly —
                        text is not extracted on this server first.
                    </p>
                    {!fileReadyToPublish && (
                        <p className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            The stored file is missing. Replace the file before
                            publishing.
                        </p>
                    )}
                </section>

                {document.status === 'draft' && canPublish && (
                    <section className="rounded-lg border border-primary/20 bg-card p-4">
                        <h2 className="mb-2 text-sm font-semibold text-mono">
                            Publish to the AI Tutor
                        </h2>
                        <p className="mb-3 text-xs text-muted-foreground">
                            Publishing uploads the file to File Search and makes
                            it available to students working on this course,
                            level, and unit.
                        </p>
                        <form onSubmit={submitPublish} className="space-y-3">
                            <label className="flex items-start gap-2 text-sm text-secondary-foreground">
                                <input
                                    type="checkbox"
                                    className="mt-0.5 h-4 w-4 rounded border-input text-primary focus:ring-ring/50"
                                    checked={publishForm.data.preview_confirmed}
                                    onChange={(e) =>
                                        publishForm.setData(
                                            'preview_confirmed',
                                            e.target.checked,
                                        )
                                    }
                                />
                                <span>
                                    I have checked the file details above and
                                    want to upload it to the AI Tutor.
                                </span>
                            </label>
                            {(publishForm.errors.preview_confirmed ||
                                errors.preview_confirmed) && (
                                <p className="text-xs text-red-600">
                                    {publishForm.errors.preview_confirmed ??
                                        errors.preview_confirmed}
                                </p>
                            )}
                            <button
                                type="submit"
                                className={primaryButtonClass}
                                disabled={
                                    publishForm.processing ||
                                    !publishForm.data.preview_confirmed ||
                                    !fileReadyToPublish
                                }
                            >
                                {publishForm.processing
                                    ? 'Publishing...'
                                    : 'Publish to the AI Tutor'}
                            </button>
                        </form>
                    </section>
                )}

                {versions.length > 0 && (
                    <section className="rounded-lg border border-border bg-card p-4">
                        <h2 className="mb-2 text-sm font-semibold text-mono">
                            Version history
                        </h2>
                        <p className="mb-3 text-xs text-muted-foreground">
                            Previous file versions are kept when you replace a
                            document. Restore an older version to create a new
                            draft from that file.
                        </p>
                        <ul className="divide-y divide-border rounded-md border border-border">
                            {versions.map((entry) => (
                                <li
                                    key={entry.version}
                                    className="flex flex-wrap items-center justify-between gap-3 px-3 py-2 text-sm"
                                >
                                    <div>
                                        <p className="font-medium text-secondary-foreground">
                                            v{entry.version} —{' '}
                                            {entry.original_filename}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Saved {entry.created_at ?? '-'}
                                            {entry.published_at
                                                ? ` · Published ${entry.published_at}`
                                                : ''}
                                        </p>
                                    </div>
                                    {entry.can_restore && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                restoreVersion(entry.version)
                                            }
                                            className={secondaryButtonClass}
                                        >
                                            Restore
                                        </button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <section className="rounded-lg border border-border bg-card p-4">
                    <h2 className="mb-3 text-sm font-semibold text-mono">
                        Manage
                    </h2>
                    <div className="flex flex-wrap items-center gap-2">
                        {document.status === 'published' && canArchive && (
                            <button
                                type="button"
                                onClick={archive}
                                className={secondaryButtonClass}
                            >
                                Archive
                            </button>
                        )}
                        {canTryAgain && canReindex && (
                            <button
                                type="button"
                                onClick={tryAgain}
                                className={secondaryButtonClass}
                            >
                                Try again
                            </button>
                        )}
                        {canReplace && (
                            <button
                                type="button"
                                onClick={() => setShowReplace(!showReplace)}
                                className={secondaryButtonClass}
                            >
                                Replace file
                            </button>
                        )}
                        {canDelete && (
                            <button
                                type="button"
                                onClick={destroy}
                                className={dangerButtonClass}
                            >
                                Delete permanently
                            </button>
                        )}
                    </div>

                    {showReplace && canReplace && (
                        <form
                            onSubmit={submitReplace}
                            className="mt-4 space-y-3 rounded-md border border-border bg-muted/50 p-3"
                        >
                            <p className="text-xs text-muted-foreground">
                                Uploading a new file creates a new version and
                                takes the document back to draft so you can
                                review it. If a published version is live for
                                students, it stays available to the AI Tutor
                                until you publish the new one.
                            </p>
                            <input
                                type="file"
                                className={inputClass}
                                onChange={(e) =>
                                    replaceForm.setData(
                                        'file',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            {(replaceForm.errors.file || errors.file) && (
                                <p className="text-xs text-red-600">
                                    {replaceForm.errors.file ?? errors.file}
                                </p>
                            )}
                            <button
                                type="submit"
                                className={primaryButtonClass}
                                disabled={
                                    replaceForm.processing ||
                                    !replaceForm.data.file
                                }
                            >
                                {replaceForm.processing
                                    ? 'Uploading...'
                                    : 'Upload new version'}
                            </button>
                        </form>
                    )}
                </section>
            </div>
        </GlcLayout>
    );
}
