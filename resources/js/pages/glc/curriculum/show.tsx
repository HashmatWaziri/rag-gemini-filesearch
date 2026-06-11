import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import {
    dangerButtonClass,
    indexBadgeClass,
    inputClass,
    primaryButtonClass,
    secondaryButtonClass,
    statusBadgeClass,
} from './components/types';

interface DocumentDetail {
    id: number;
    title: string;
    course: string;
    level: string;
    unit: string;
    lesson: string | null;
    format: string;
    original_filename: string;
    status: string;
    status_label: string;
    index_status: string;
    index_status_label: string;
    index_error: string | null;
    version: number;
    extracted_text: string | null;
    uploaded_by: string | null;
    published_at: string | null;
    archived_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

interface CurriculumShowProps {
    document: DocumentDetail;
    canDelete: boolean;
    status: string | null;
}

export default function CurriculumShow({
    document,
    canDelete,
    status,
}: CurriculumShowProps) {
    const { errors } = usePage().props as { errors: Record<string, string> };

    const publishForm = useForm({ preview_confirmed: false });
    const replaceForm = useForm<{ file: File | null }>({ file: null });
    const [showReplace, setShowReplace] = useState(false);

    const baseUrl = `/staff/curriculum/documents/${document.id}`;

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
                'Archive this document? It will be removed from tutor retrieval.',
            )
        ) {
            router.post(`${baseUrl}/archive`, {}, { preserveScroll: true });
        }
    };

    const reindex = () =>
        router.post(`${baseUrl}/reindex`, {}, { preserveScroll: true });

    const destroy = () => {
        if (
            confirm(
                'Permanently delete this document, its file, and its search index entry? This cannot be undone.',
            )
        ) {
            router.delete(baseUrl);
        }
    };

    const metadata: [string, string][] = [
        ['Course', document.course],
        ['Level', document.level],
        ['Unit', document.unit],
        ['Lesson', document.lesson ?? 'Not set'],
        ['Format', document.format.toUpperCase()],
        ['Original filename', document.original_filename],
        ['Version', `v${document.version}`],
        ['Uploaded by', document.uploaded_by ?? 'Unknown'],
        ['Uploaded at', document.created_at ?? '-'],
        ['Last updated', document.updated_at ?? '-'],
        ['Published at', document.published_at ?? '-'],
        ['Archived at', document.archived_at ?? '-'],
    ];

    const canReindex =
        document.status === 'published' && document.index_status !== 'indexing';

    return (
        <GlcLayout title={document.title}>
            <Head title={document.title} />

            <div className="space-y-6">
                <Link
                    href="/staff/curriculum"
                    className="text-sm font-medium text-emerald-700 hover:underline"
                >
                    Back to curriculum
                </Link>

                {status && (
                    <div className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {status}
                    </div>
                )}

                {errors.status && (
                    <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {errors.status}
                    </div>
                )}

                <section className="rounded-lg border border-slate-200 bg-white p-4">
                    <div className="mb-3 flex flex-wrap items-center gap-2">
                        <span className={statusBadgeClass(document.status)}>
                            {document.status_label}
                        </span>
                        <span
                            className={indexBadgeClass(document.index_status)}
                        >
                            Index: {document.index_status_label}
                        </span>
                    </div>

                    {document.index_error && (
                        <div className="mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                            Indexing error: {document.index_error}
                        </div>
                    )}

                    <dl className="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
                        {metadata.map(([label, value]) => (
                            <div key={label}>
                                <dt className="text-xs font-medium tracking-wide text-slate-400 uppercase">
                                    {label}
                                </dt>
                                <dd className="text-sm text-slate-700">
                                    {value}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </section>

                <section className="rounded-lg border border-slate-200 bg-white p-4">
                    <h2 className="mb-2 text-sm font-semibold text-slate-800">
                        Extracted text preview
                    </h2>
                    <p className="mb-3 text-xs text-slate-500">
                        This is the text the AI tutor will retrieve. Review it
                        before publishing.
                    </p>
                    <pre className="max-h-96 overflow-auto rounded-md border border-slate-200 bg-slate-50 p-3 text-xs leading-relaxed whitespace-pre-wrap text-slate-700">
                        {document.extracted_text?.trim() ||
                            'No text could be extracted from this file.'}
                    </pre>
                </section>

                {document.status === 'draft' && (
                    <section className="rounded-lg border border-emerald-200 bg-white p-4">
                        <h2 className="mb-2 text-sm font-semibold text-slate-800">
                            Publish
                        </h2>
                        <p className="mb-3 text-xs text-slate-500">
                            Publishing makes this document retrievable by the AI
                            tutor for students assigned to its course, level,
                            and unit.
                        </p>
                        <form onSubmit={submitPublish} className="space-y-3">
                            <label className="flex items-start gap-2 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    className="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                    checked={publishForm.data.preview_confirmed}
                                    onChange={(e) =>
                                        publishForm.setData(
                                            'preview_confirmed',
                                            e.target.checked,
                                        )
                                    }
                                />
                                <span>
                                    I have reviewed the extracted text preview
                                    above and it is correct.
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
                                    !publishForm.data.preview_confirmed
                                }
                            >
                                {publishForm.processing
                                    ? 'Publishing...'
                                    : 'Publish document'}
                            </button>
                        </form>
                    </section>
                )}

                <section className="rounded-lg border border-slate-200 bg-white p-4">
                    <h2 className="mb-3 text-sm font-semibold text-slate-800">
                        Manage
                    </h2>
                    <div className="flex flex-wrap items-center gap-2">
                        {document.status === 'published' && (
                            <button
                                type="button"
                                onClick={archive}
                                className={secondaryButtonClass}
                            >
                                Archive
                            </button>
                        )}
                        {canReindex && (
                            <button
                                type="button"
                                onClick={reindex}
                                className={secondaryButtonClass}
                            >
                                Reindex
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={() => setShowReplace(!showReplace)}
                            className={secondaryButtonClass}
                        >
                            Replace file
                        </button>
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

                    {showReplace && (
                        <form
                            onSubmit={submitReplace}
                            className="mt-4 space-y-3 rounded-md border border-slate-200 bg-slate-50 p-3"
                        >
                            <p className="text-xs text-slate-500">
                                Uploading a replacement bumps the version,
                                returns the document to draft, and removes the
                                old version from the search index. Review the
                                new extracted text and publish again.
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
                                    ? 'Replacing...'
                                    : 'Replace and re-extract'}
                            </button>
                        </form>
                    )}
                </section>
            </div>
        </GlcLayout>
    );
}
