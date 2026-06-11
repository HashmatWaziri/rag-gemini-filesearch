import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router } from '@inertiajs/react';
import HierarchyManager from './components/hierarchy-manager';
import HierarchyPicker from './components/hierarchy-picker';
import {
    emptySelection,
    indexBadgeClass,
    inputClass,
    labelClass,
    statusBadgeClass,
    type BulkReportRow,
    type DocumentRow,
    type Paginated,
    type TreeCourse,
    type UploadConfig,
} from './components/types';
import UploadPanel from './components/upload-panel';

interface Filters {
    course_id?: number | string | null;
    course_level_id?: number | string | null;
    course_unit_id?: number | string | null;
    course_lesson_id?: number | string | null;
    status?: string | null;
    index_status?: string | null;
}

interface CurriculumIndexProps {
    documents: Paginated<DocumentRow>;
    filters: Filters;
    tree: TreeCourse[];
    upload: UploadConfig;
    bulkReport: BulkReportRow[] | null;
    status: string | null;
}

const STATUS_OPTIONS = [
    ['draft', 'Draft'],
    ['published', 'Published'],
    ['archived', 'Archived'],
] as const;

const INDEX_STATUS_OPTIONS = [
    ['pending', 'Pending'],
    ['indexing', 'Indexing'],
    ['indexed', 'Indexed'],
    ['failed', 'Failed'],
    ['removed', 'Removed'],
] as const;

export default function CurriculumIndex({
    documents,
    filters,
    tree,
    upload,
    bulkReport,
    status,
}: CurriculumIndexProps) {
    const applyFilters = (next: Record<string, string>) => {
        const query = Object.fromEntries(
            Object.entries(next).filter(([, value]) => value !== ''),
        );
        router.get('/staff/curriculum', query, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const currentFilters = {
        course_id: filters.course_id ? String(filters.course_id) : '',
        course_level_id: filters.course_level_id
            ? String(filters.course_level_id)
            : '',
        course_unit_id: filters.course_unit_id
            ? String(filters.course_unit_id)
            : '',
        course_lesson_id: filters.course_lesson_id
            ? String(filters.course_lesson_id)
            : '',
        status: filters.status ?? '',
        index_status: filters.index_status ?? '',
    };

    return (
        <GlcLayout title="Curriculum">
            <Head title="Curriculum" />

            <div className="space-y-6">
                {status && (
                    <div className="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {status}
                    </div>
                )}

                {bulkReport && bulkReport.length > 0 && (
                    <section className="rounded-lg border border-slate-200 bg-white p-4">
                        <h2 className="mb-2 text-sm font-semibold text-slate-800">
                            Bulk upload report
                        </h2>
                        <ul className="divide-y divide-slate-100">
                            {bulkReport.map((row) => (
                                <li
                                    key={row.filename}
                                    className="flex flex-col gap-1 py-2 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <span className="truncate text-sm text-slate-700">
                                        {row.filename}
                                    </span>
                                    {row.success ? (
                                        <span className="inline-flex w-fit items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                            Uploaded as draft
                                        </span>
                                    ) : (
                                        <span className="inline-flex w-fit items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                                            {row.error ?? 'Failed'}
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                <UploadPanel tree={tree} upload={upload} />

                <section className="rounded-lg border border-slate-200 bg-white p-4">
                    <h2 className="mb-3 text-sm font-semibold text-slate-800">
                        Documents
                    </h2>

                    <div className="mb-4 space-y-3">
                        <HierarchyPicker
                            tree={tree}
                            value={{
                                course_id: currentFilters.course_id,
                                course_level_id: currentFilters.course_level_id,
                                course_unit_id: currentFilters.course_unit_id,
                                course_lesson_id:
                                    currentFilters.course_lesson_id,
                            }}
                            onChange={(value) =>
                                applyFilters({
                                    ...currentFilters,
                                    ...value,
                                })
                            }
                            allowEmpty
                        />
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label className={labelClass}>Status</label>
                                <select
                                    className={inputClass}
                                    value={currentFilters.status}
                                    onChange={(e) =>
                                        applyFilters({
                                            ...currentFilters,
                                            status: e.target.value,
                                        })
                                    }
                                >
                                    <option value="">All statuses</option>
                                    {STATUS_OPTIONS.map(([value, label]) => (
                                        <option key={value} value={value}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className={labelClass}>
                                    Index status
                                </label>
                                <select
                                    className={inputClass}
                                    value={currentFilters.index_status}
                                    onChange={(e) =>
                                        applyFilters({
                                            ...currentFilters,
                                            index_status: e.target.value,
                                        })
                                    }
                                >
                                    <option value="">All index statuses</option>
                                    {INDEX_STATUS_OPTIONS.map(
                                        ([value, label]) => (
                                            <option key={value} value={value}>
                                                {label}
                                            </option>
                                        ),
                                    )}
                                </select>
                            </div>
                            <div className="flex items-end lg:col-span-2">
                                <button
                                    type="button"
                                    className="text-sm font-medium text-slate-500 underline-offset-2 hover:underline"
                                    onClick={() =>
                                        applyFilters({
                                            ...emptySelection,
                                            status: '',
                                            index_status: '',
                                        })
                                    }
                                >
                                    Clear filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[640px] text-left text-sm">
                            <thead>
                                <tr className="border-b border-slate-200 text-xs tracking-wide text-slate-500 uppercase">
                                    <th className="px-2 py-2">Title</th>
                                    <th className="px-2 py-2">Path</th>
                                    <th className="px-2 py-2">Format</th>
                                    <th className="px-2 py-2">Status</th>
                                    <th className="px-2 py-2">Index</th>
                                    <th className="px-2 py-2">Version</th>
                                    <th className="px-2 py-2">Updated</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {documents.data.map((doc) => (
                                    <tr
                                        key={doc.id}
                                        className="hover:bg-slate-50"
                                    >
                                        <td className="px-2 py-2">
                                            <Link
                                                href={`/staff/curriculum/documents/${doc.id}`}
                                                className="font-medium text-emerald-700 hover:underline"
                                            >
                                                {doc.title}
                                            </Link>
                                        </td>
                                        <td className="px-2 py-2 text-xs text-slate-500">
                                            {doc.course} / {doc.level} /{' '}
                                            {doc.unit}
                                            {doc.lesson
                                                ? ` / ${doc.lesson}`
                                                : ''}
                                        </td>
                                        <td className="px-2 py-2 text-xs text-slate-500 uppercase">
                                            {doc.format}
                                        </td>
                                        <td className="px-2 py-2">
                                            <span
                                                className={statusBadgeClass(
                                                    doc.status,
                                                )}
                                            >
                                                {doc.status_label}
                                            </span>
                                        </td>
                                        <td className="px-2 py-2">
                                            <span
                                                className={indexBadgeClass(
                                                    doc.index_status,
                                                )}
                                            >
                                                {doc.index_status_label}
                                            </span>
                                        </td>
                                        <td className="px-2 py-2 text-slate-600">
                                            v{doc.version}
                                        </td>
                                        <td className="px-2 py-2 text-xs text-slate-500">
                                            {doc.updated_at}
                                        </td>
                                    </tr>
                                ))}
                                {documents.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-2 py-6 text-center text-sm text-slate-400"
                                        >
                                            No documents match the current
                                            filters.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {documents.links.length > 3 && (
                        <nav className="mt-4 flex flex-wrap gap-1">
                            {documents.links.map((link, index) =>
                                link.url ? (
                                    <Link
                                        key={index}
                                        href={link.url}
                                        preserveScroll
                                        className={`rounded px-2.5 py-1 text-xs font-medium ${
                                            link.active
                                                ? 'bg-emerald-600 text-white'
                                                : 'border border-slate-200 text-slate-600 hover:bg-slate-50'
                                        }`}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        key={index}
                                        className="rounded px-2.5 py-1 text-xs text-slate-300"
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ),
                            )}
                        </nav>
                    )}
                </section>

                <HierarchyManager tree={tree} />
            </div>
        </GlcLayout>
    );
}
