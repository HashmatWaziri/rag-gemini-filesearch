import { type FormEvent } from 'react';
import { generateUUID } from '@/lib/utils';
import MaterialKindSelect from './material-kind-select';
import {
    type LessonMaterialRow,
    type LessonUploadCapacity,
    type MaterialKindOption,
    type UploadConfig,
} from './types';
import { inputClass, labelClass, primaryButtonClass, secondaryButtonClass } from './ui';

interface LessonMaterialsRepeaterProps {
    lessonId: string;
    lessonName: string | null;
    rows: LessonMaterialRow[];
    onRowsChange: (rows: LessonMaterialRow[]) => void;
    materialKinds: MaterialKindOption[];
    upload: UploadConfig;
    capacity: LessonUploadCapacity | null;
    capacityLoading: boolean;
    acceptAttribute: string;
    processing: boolean;
    serverError: string | null;
    onSubmit: () => void;
}

function duplicateFilenames(rows: LessonMaterialRow[]): string[] {
    const seen = new Map<string, number>();
    const duplicates: string[] = [];

    for (const row of rows) {
        if (!row.file) {
            continue;
        }

        const key = row.file.name.toLowerCase();

        if (seen.has(key)) {
            duplicates.push(row.file.name);
        } else {
            seen.set(key, 1);
        }
    }

    return duplicates;
}

export default function LessonMaterialsRepeater({
    lessonId,
    lessonName,
    rows,
    onRowsChange,
    materialKinds,
    upload,
    capacity,
    capacityLoading,
    acceptAttribute,
    processing,
    serverError,
    onSubmit,
}: LessonMaterialsRepeaterProps) {
    const maxRows =
        capacity?.max_rows ??
        Math.min(
            upload.maxBulkFiles,
            upload.maxDocumentsPerLesson ?? upload.maxBulkFiles,
        );

    const rowsWithFiles = rows.filter((row) => row.file !== null);
    const canAddRow = rows.length < maxRows && maxRows > 0;
    const lessonFull = capacity !== null && capacity.remaining_slots === 0;
    const duplicates = duplicateFilenames(rowsWithFiles);

    const addRow = () => {
        if (!canAddRow) {
            return;
        }

        onRowsChange([
            ...rows,
            {
                id: generateUUID(),
                material_kind: 'summary',
                title: '',
                file: null,
            },
        ]);
    };

    const removeRow = (id: string) => {
        if (rows.length <= 1) {
            return;
        }

        onRowsChange(rows.filter((row) => row.id !== id));
    };

    const updateRow = (
        id: string,
        patch: Partial<Omit<LessonMaterialRow, 'id'>>,
    ) => {
        onRowsChange(
            rows.map((row) => (row.id === id ? { ...row, ...patch } : row)),
        );
    };

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();

        if (!lessonId) {
            return;
        }

        if (rowsWithFiles.length === 0) {
            return;
        }

        if (rowsWithFiles.length > maxRows) {
            return;
        }

        if (duplicates.length > 0) {
            return;
        }

        if (rowsWithFiles.some((row) => !row.material_kind)) {
            return;
        }

        onSubmit();
    };

    const clientBlocked =
        !lessonId ||
        lessonFull ||
        rowsWithFiles.length === 0 ||
        rowsWithFiles.length > maxRows ||
        duplicates.length > 0;

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            {lessonName && (
                <div className="rounded-md border border-border bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
                    <p>
                        Tag each file for{' '}
                        <span className="font-medium text-foreground">
                            {lessonName}
                        </span>{' '}
                        — summaries, notes, worksheets, and approved PDFs can
                        all live on the same lesson.
                    </p>
                    {capacityLoading ? (
                        <p className="mt-1">Checking lesson capacity…</p>
                    ) : capacity ? (
                        <p className="mt-1">
                            {capacity.existing_count} of{' '}
                            {capacity.max_per_lesson} document(s) on this
                            lesson. You can add up to{' '}
                            <span className="font-medium text-foreground">
                                {capacity.max_rows}
                            </span>{' '}
                            tagged file(s) in this batch (
                            {capacity.max_per_request} max per upload).
                        </p>
                    ) : null}
                </div>
            )}

            {lessonFull && (
                <p className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                    This lesson already has the maximum number of documents (
                    {upload.maxDocumentsPerLesson}). Archive or delete existing
                    materials before adding more.
                </p>
            )}

            <div className="space-y-3">
                {rows.map((row, index) => (
                    <div
                        key={row.id}
                        className="rounded-md border border-border p-3"
                    >
                        <div className="mb-2 flex items-center justify-between gap-2">
                            <p className="text-xs font-medium text-muted-foreground">
                                Tagged file {index + 1}
                            </p>
                            {rows.length > 1 && (
                                <button
                                    type="button"
                                    className="text-xs text-muted-foreground underline-offset-2 hover:underline"
                                    onClick={() => removeRow(row.id)}
                                >
                                    Remove
                                </button>
                            )}
                        </div>
                        <div className="grid grid-cols-1 gap-3 lg:grid-cols-3">
                            <MaterialKindSelect
                                value={row.material_kind}
                                onChange={(material_kind) =>
                                    updateRow(row.id, { material_kind })
                                }
                                options={materialKinds}
                                label="Tag"
                                placeholder="Select tag"
                                disabled={lessonFull}
                            />
                            <div>
                                <label className={labelClass}>
                                    Title (optional)
                                </label>
                                <input
                                    type="text"
                                    className={inputClass}
                                    value={row.title}
                                    onChange={(e) =>
                                        updateRow(row.id, {
                                            title: e.target.value,
                                        })
                                    }
                                    placeholder="Defaults to the file name"
                                    disabled={lessonFull}
                                />
                            </div>
                            <div>
                                <label className={labelClass}>File</label>
                                <input
                                    type="file"
                                    accept={acceptAttribute}
                                    className={inputClass}
                                    disabled={lessonFull}
                                    onChange={(e) =>
                                        updateRow(row.id, {
                                            file: e.target.files?.[0] ?? null,
                                        })
                                    }
                                />
                                {row.file && (
                                    <p className="mt-1 truncate text-xs text-muted-foreground">
                                        {row.file.name}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {duplicates.length > 0 && (
                <p className="text-xs text-red-600">
                    Remove duplicate files in this batch:{' '}
                    {duplicates.join(', ')}
                </p>
            )}

            {rowsWithFiles.length > maxRows && (
                <p className="text-xs text-red-600">
                    This batch has {rowsWithFiles.length} files but only{' '}
                    {maxRows} slot(s) remain for this lesson.
                </p>
            )}

            <div className="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    className={secondaryButtonClass}
                    onClick={addRow}
                    disabled={!canAddRow || lessonFull}
                >
                    Add another tagged file
                </button>
                <button
                    type="submit"
                    className={primaryButtonClass}
                    disabled={processing || clientBlocked}
                >
                    {processing
                        ? 'Uploading...'
                        : `Upload ${rowsWithFiles.length || 0} tagged file(s) as drafts`}
                </button>
            </div>

            {!lessonId && (
                <p className="text-xs text-muted-foreground">
                    Select a lesson above before adding tagged files.
                </p>
            )}

            {serverError && (
                <p className="text-xs text-red-600">{serverError}</p>
            )}
        </form>
    );
}
