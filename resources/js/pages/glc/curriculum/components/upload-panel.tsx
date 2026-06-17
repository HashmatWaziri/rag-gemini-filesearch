import { router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { generateUUID } from '@/lib/utils';
import HierarchyPicker from './hierarchy-picker';
import LessonMaterialsRepeater from './lesson-materials-repeater';
import {
    emptySelection,
    type LessonMaterialRow,
    type LessonUploadCapacity,
    type MaterialKindOption,
    type TreeCourse,
    type UploadConfig,
} from './types';

interface UploadPanelProps {
    tree: TreeCourse[];
    upload: UploadConfig;
    materialKinds: MaterialKindOption[];
}

interface LessonForm {
    course_id: string;
    course_level_id: string;
    course_unit_id: string;
    course_lesson_id: string;
    [key: string]: string;
}

function newLessonRow(): LessonMaterialRow {
    return {
        id: generateUUID(),
        material_kind: 'summary',
        title: '',
        file: null,
    };
}

function buildLessonMaterialsFormData(
    hierarchy: LessonForm,
    rowsWithFiles: LessonMaterialRow[],
): FormData {
    const formData = new FormData();

    formData.append('course_id', hierarchy.course_id);
    formData.append('course_level_id', hierarchy.course_level_id);
    formData.append('course_unit_id', hierarchy.course_unit_id);
    formData.append('course_lesson_id', hierarchy.course_lesson_id);

    rowsWithFiles.forEach((row, index) => {
        formData.append(`material_kinds[${index}]`, row.material_kind);

        if (row.title.trim() !== '') {
            formData.append(`titles[${index}]`, row.title.trim());
        }

        if (row.file instanceof File) {
            formData.append(`files[${index}]`, row.file, row.file.name);
        }
    });

    return formData;
}

export default function UploadPanel({
    tree,
    upload,
    materialKinds,
}: UploadPanelProps) {
    const [lessonRows, setLessonRows] = useState<LessonMaterialRow[]>([
        newLessonRow(),
    ]);
    const [lessonCapacity, setLessonCapacity] =
        useState<LessonUploadCapacity | null>(null);
    const [capacityLoading, setCapacityLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [clientError, setClientError] = useState<string | null>(null);

    const form = useForm<LessonForm>({
        ...emptySelection,
    });

    useEffect(() => {
        const lessonId = form.data.course_lesson_id;

        if (!lessonId) {
            setLessonCapacity(null);
            setCapacityLoading(false);
            return;
        }

        let cancelled = false;
        setCapacityLoading(true);

        fetch(`/staff/curriculum/lessons/${lessonId}/upload-capacity`, {
            headers: { Accept: 'application/json' },
        })
            .then((response) => response.json())
            .then((data: LessonUploadCapacity) => {
                if (cancelled) {
                    return;
                }

                setLessonCapacity(data);

                if (data.max_rows < 1) {
                    setLessonRows([newLessonRow()]);
                    return;
                }

                setLessonRows((rows) => {
                    const withFiles = rows.filter((row) => row.file !== null);

                    if (withFiles.length > data.max_rows) {
                        return withFiles.slice(0, data.max_rows);
                    }

                    if (rows.length > data.max_rows) {
                        return rows.slice(0, data.max_rows);
                    }

                    return rows.length === 0 ? [newLessonRow()] : rows;
                });
            })
            .finally(() => {
                if (!cancelled) {
                    setCapacityLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [form.data.course_lesson_id]);

    const submitUpload = () => {
        setClientError(null);

        if (!form.data.course_lesson_id) {
            setClientError('Select a lesson before uploading tagged materials.');
            return;
        }

        const rowsWithFiles = lessonRows.filter(
            (row): row is LessonMaterialRow & { file: File } =>
                row.file instanceof File,
        );

        if (rowsWithFiles.length === 0) {
            setClientError('Add at least one file to upload.');
            return;
        }

        const formData = buildLessonMaterialsFormData(form.data, rowsWithFiles);

        setSubmitting(true);

        router.post(
            '/staff/curriculum/documents/lesson-materials',
            formData,
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                    setLessonRows([newLessonRow()]);
                },
                onError: (errors) => {
                    const firstError = Object.values(errors)[0];
                    setClientError(
                        typeof firstError === 'string'
                            ? firstError
                            : 'Upload failed. Check each file and try again.',
                    );
                },
                onFinish: () => {
                    setSubmitting(false);
                },
            },
        );
    };

    const acceptAttribute = upload.allowedExtensions
        .map((ext) => `.${ext}`)
        .join(',');

    const selectedLesson =
        form.data.course_lesson_id &&
        tree
            .find((course) => String(course.id) === form.data.course_id)
            ?.levels.find(
                (level) => String(level.id) === form.data.course_level_id,
            )
            ?.units.find(
                (unit) => String(unit.id) === form.data.course_unit_id,
            )
            ?.lessons.find(
                (item) => String(item.id) === form.data.course_lesson_id,
            );

    const serverError =
        clientError ??
        form.errors.files ??
        form.errors.material_kinds ??
        form.errors.course_lesson_id ??
        form.errors.course_id ??
        form.errors.course_level_id ??
        form.errors.course_unit_id ??
        null;

    return (
        <section className="rounded-lg border border-border bg-card p-4">
            <div className="mb-3">
                <h2 className="text-sm font-semibold text-mono">
                    Upload tagged curriculum materials
                </h2>
                <p className="mt-1 text-xs text-muted-foreground">
                    Select a lesson, then add one row per file. Tag each file
                    (summary, notes, worksheet, approved PDF) and upload them
                    together as drafts.
                </p>
            </div>

            <p className="mb-4 text-xs text-muted-foreground">
                Accepted formats: {upload.allowedExtensions.join(', ')} (max{' '}
                {Math.round(upload.maxFileSizeKb / 1024)} MB per file,{' '}
                {upload.maxBulkFiles} files per upload,{' '}
                {upload.maxDocumentsPerLesson ?? upload.maxBulkFiles} documents
                per lesson).
            </p>

            <div className="space-y-4">
                <HierarchyPicker
                    tree={tree}
                    value={{
                        course_id: form.data.course_id,
                        course_level_id: form.data.course_level_id,
                        course_unit_id: form.data.course_unit_id,
                        course_lesson_id: form.data.course_lesson_id,
                    }}
                    onChange={(value) =>
                        form.setData((data) => ({ ...data, ...value }))
                    }
                    errors={form.errors}
                    requireLesson
                />

                {!form.data.course_lesson_id && (
                    <p className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        Choose a lesson above to unlock the tagged-file
                        repeater. Each lesson can hold many materials — add
                        rows with the button below once a lesson is selected.
                    </p>
                )}

                <LessonMaterialsRepeater
                    lessonId={form.data.course_lesson_id}
                    lessonName={selectedLesson ? selectedLesson.name : null}
                    rows={lessonRows}
                    onRowsChange={setLessonRows}
                    materialKinds={materialKinds}
                    upload={upload}
                    capacity={lessonCapacity}
                    capacityLoading={capacityLoading}
                    acceptAttribute={acceptAttribute}
                    processing={submitting}
                    serverError={serverError}
                    onSubmit={submitUpload}
                />
            </div>
        </section>
    );
}
