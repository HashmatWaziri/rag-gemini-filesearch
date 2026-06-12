import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import HierarchyPicker from './hierarchy-picker';
import {
    emptySelection,
    type HierarchySelection,
    type TreeCourse,
    type UploadConfig,
} from './types';
import {
    inputClass,
    labelClass,
    primaryButtonClass,
} from './ui';

interface UploadPanelProps {
    tree: TreeCourse[];
    upload: UploadConfig;
}

interface SingleForm extends HierarchySelection {
    title: string;
    file: File | null;
    [key: string]: string | File | null;
}

interface BulkForm extends HierarchySelection {
    files: File[];
    [key: string]: string | File[];
}

export default function UploadPanel({ tree, upload }: UploadPanelProps) {
    const [mode, setMode] = useState<'single' | 'bulk'>('single');

    const single = useForm<SingleForm>({
        ...emptySelection,
        title: '',
        file: null,
    });

    const bulk = useForm<BulkForm>({
        ...emptySelection,
        files: [],
    });

    const submitSingle = (e: FormEvent) => {
        e.preventDefault();
        single.post('/staff/curriculum/documents', {
            forceFormData: true,
        });
    };

    const submitBulk = (e: FormEvent) => {
        e.preventDefault();
        bulk.post('/staff/curriculum/documents/bulk', {
            forceFormData: true,
            onSuccess: () => bulk.reset(),
        });
    };

    const acceptAttribute = upload.allowedExtensions
        .map((ext) => `.${ext}`)
        .join(',');

    const tabClass = (active: boolean) =>
        `rounded-md px-3 py-1.5 text-sm font-medium ${
            active
                ? 'bg-primary text-primary-foreground'
                : 'bg-card text-secondary-foreground hover:bg-accent'
        }`;

    return (
        <section className="rounded-lg border border-border bg-card p-4">
            <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 className="text-sm font-semibold text-mono">
                    Upload curriculum documents
                </h2>
                <div className="flex gap-1 rounded-lg border border-border p-0.5">
                    <button
                        type="button"
                        className={tabClass(mode === 'single')}
                        onClick={() => setMode('single')}
                    >
                        Single
                    </button>
                    <button
                        type="button"
                        className={tabClass(mode === 'bulk')}
                        onClick={() => setMode('bulk')}
                    >
                        Bulk
                    </button>
                </div>
            </div>

            <p className="mb-4 text-xs text-muted-foreground">
                Accepted formats: {upload.allowedExtensions.join(', ')} (max{' '}
                {Math.round(upload.maxFileSizeKb / 1024)} MB per file). New
                uploads start as drafts and are not visible to students or the
                AI Tutor until you publish them.
            </p>

            {mode === 'single' ? (
                <form onSubmit={submitSingle} className="space-y-4">
                    <HierarchyPicker
                        tree={tree}
                        value={{
                            course_id: single.data.course_id,
                            course_level_id: single.data.course_level_id,
                            course_unit_id: single.data.course_unit_id,
                            course_lesson_id: single.data.course_lesson_id,
                        }}
                        onChange={(value) =>
                            single.setData((data) => ({ ...data, ...value }))
                        }
                        errors={single.errors}
                    />

                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label className={labelClass}>Title</label>
                            <input
                                type="text"
                                className={inputClass}
                                value={single.data.title}
                                onChange={(e) =>
                                    single.setData('title', e.target.value)
                                }
                                placeholder="e.g. Unit 3 grammar summary"
                            />
                            {single.errors.title && (
                                <p className="mt-1 text-xs text-red-600">
                                    {single.errors.title}
                                </p>
                            )}
                        </div>
                        <div>
                            <label className={labelClass}>File</label>
                            <input
                                type="file"
                                accept={acceptAttribute}
                                className={inputClass}
                                onChange={(e) =>
                                    single.setData(
                                        'file',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            {single.errors.file && (
                                <p className="mt-1 text-xs text-red-600">
                                    {single.errors.file}
                                </p>
                            )}
                        </div>
                    </div>

                    <button
                        type="submit"
                        className={primaryButtonClass}
                        disabled={single.processing}
                    >
                        {single.processing ? 'Uploading...' : 'Upload as draft'}
                    </button>
                </form>
            ) : (
                <form onSubmit={submitBulk} className="space-y-4">
                    <HierarchyPicker
                        tree={tree}
                        value={{
                            course_id: bulk.data.course_id,
                            course_level_id: bulk.data.course_level_id,
                            course_unit_id: bulk.data.course_unit_id,
                            course_lesson_id: bulk.data.course_lesson_id,
                        }}
                        onChange={(value) =>
                            bulk.setData((data) => ({ ...data, ...value }))
                        }
                        errors={bulk.errors}
                    />

                    <div>
                        <label className={labelClass}>
                            Files (up to {upload.maxBulkFiles}, sharing the tags
                            above; each file's name becomes its title)
                        </label>
                        <input
                            type="file"
                            multiple
                            accept={acceptAttribute}
                            className={inputClass}
                            onChange={(e) =>
                                bulk.setData(
                                    'files',
                                    Array.from(e.target.files ?? []),
                                )
                            }
                        />
                        {bulk.data.files.length > 0 && (
                            <p className="mt-1 text-xs text-muted-foreground">
                                {bulk.data.files.length} file(s) selected
                            </p>
                        )}
                        {bulk.errors.files && (
                            <p className="mt-1 text-xs text-red-600">
                                {bulk.errors.files}
                            </p>
                        )}
                    </div>

                    <button
                        type="submit"
                        className={primaryButtonClass}
                        disabled={bulk.processing}
                    >
                        {bulk.processing
                            ? 'Uploading...'
                            : `Upload ${bulk.data.files.length || ''} file(s) as drafts`}
                    </button>
                </form>
            )}
        </section>
    );
}
