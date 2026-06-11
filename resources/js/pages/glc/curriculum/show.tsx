import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import {
    dangerButtonClass,
    inputClass,
    primaryButtonClass,
    secondaryButtonClass,
    stateBadgeClass,
    type DocumentState,
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
    index_status: string;
    index_error: string | null;
    state: DocumentState;
    state_label: string;
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
    const hasPreviewText = Boolean(document.extracted_text?.trim());

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

    const metadata: [string, string][] = [
        ['Course', document.course],
        ['Level', document.level],
        ['Unit', document.unit],
        ['Lesson', document.lesson ?? 'Whole unit'],
        ['Format', document.format.toUpperCase()],
        ['Original filename', document.original_filename],
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
                        <span className={stateBadgeClass(document.state)}>
                            {document.state_label}
                        </span>
                    </div>

                    {document.state === 'publish_failed' && (
                        <div className="mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                            <p>
                                Something went wrong while making this document
                                available to the AI Tutor. Use "Try again" below
                                — if it keeps failing, contact support.
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
                        <p className="mb-3 text-xs text-slate-500">
                            This document is being prepared for the AI Tutor.
                            This usually takes a few minutes — refresh the page
                            to see the latest status.
                        </p>
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
                        Text preview
                    </h2>
                    <p className="mb-3 text-xs text-slate-500">
                        This is the text the AI Tutor will read from this
                        document. Check that it looks right before publishing.
                    </p>
                    <pre className="max-h-96 overflow-auto rounded-md border border-slate-200 bg-slate-50 p-3 text-xs leading-relaxed whitespace-pre-wrap text-slate-700">
                        {document.extracted_text?.trim() ||
                            'No readable text was found in this file. Replace it with a readable PDF, Word, or text document before publishing.'}
                    </pre>
                </section>

                {document.status === 'draft' && (
                    <section className="rounded-lg border border-emerald-200 bg-white p-4">
                        <h2 className="mb-2 text-sm font-semibold text-slate-800">
                            Publish to the AI Tutor
                        </h2>
                        <p className="mb-3 text-xs text-slate-500">
                            Publishing makes this document available to the AI
                            Tutor for students working on this course, level,
                            and unit.
                        </p>
                        {!hasPreviewText && (
                            <p className="mb-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                This document can't be published yet because no
                                readable text was found in the file. Replace the
                                file first.
                            </p>
                        )}
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
                                    I have checked the text preview above and it
                                    looks right.
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
                                    !hasPreviewText
                                }
                            >
                                {publishForm.processing
                                    ? 'Publishing...'
                                    : 'Publish to the AI Tutor'}
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
                        {canTryAgain && (
                            <button
                                type="button"
                                onClick={tryAgain}
                                className={secondaryButtonClass}
                            >
                                Try again
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
                                Uploading a new file creates a new version and
                                takes the document back to draft so you can
                                check it. If a published version is live for
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
