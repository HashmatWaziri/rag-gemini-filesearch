import { GlcDataTableCard } from '@/components/glc';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router } from '@inertiajs/react';
import { LinkPagination } from '../admin/components';
import HierarchyManager from './components/hierarchy-manager';
import HierarchyPicker from './components/hierarchy-picker';
import {
    documentStateFilterOptions,
    emptySelection,
    type BulkReportRow,
    type DocumentRow,
    type Paginated,
    type TreeCourse,
    type UploadConfig,
} from './components/types';
import {
    inputClass,
    labelClass,
    stateBadgeClass,
} from './components/ui';
import UploadPanel from './components/upload-panel';

interface Filters {
    course_id?: number | string | null;
    course_level_id?: number | string | null;
    course_unit_id?: number | string | null;
    course_lesson_id?: number | string | null;
    state?: string | null;
}

interface CurriculumIndexProps {
    documents: Paginated<DocumentRow>;
    filters: Filters;
    tree: TreeCourse[];
    upload: UploadConfig;
    bulkReport: BulkReportRow[] | null;
    status: string | null;
}

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
        state: filters.state ?? '',
    };

    return (
        <GlcLayout title="Curriculum">
            <Head title="Curriculum" />

            <div className="space-y-6">
                {status && (
                    <div className="rounded-md border border-primary/20 bg-primary/10 px-4 py-3 text-sm text-primary">
                        {status}
                    </div>
                )}

                {bulkReport && bulkReport.length > 0 && (
                    <section className="rounded-lg border border-border bg-card p-4">
                        <h2 className="mb-2 text-sm font-semibold text-mono">
                            Bulk upload report
                        </h2>
                        <ul className="divide-y divide-border">
                            {bulkReport.map((row) => (
                                <li
                                    key={row.filename}
                                    className="flex flex-col gap-1 py-2 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <span className="truncate text-sm text-secondary-foreground">
                                        {row.filename}
                                    </span>
                                    {row.success ? (
                                        <span className="inline-flex w-fit items-center rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">
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

                <GlcDataTableCard
                    filters={
                        <div className="flex w-full flex-col gap-3">
                            <HierarchyPicker
                                tree={tree}
                                value={{
                                    course_id: currentFilters.course_id,
                                    course_level_id:
                                        currentFilters.course_level_id,
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
                            <div className="flex flex-wrap items-end gap-3">
                                <div className="w-full sm:w-48">
                                    <label className={labelClass}>Status</label>
                                    <Select
                                        value={
                                            currentFilters.state || '__all__'
                                        }
                                        onValueChange={(value) =>
                                            applyFilters({
                                                ...currentFilters,
                                                state:
                                                    value === '__all__'
                                                        ? ''
                                                        : value,
                                            })
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="All statuses" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="__all__">
                                                All statuses
                                            </SelectItem>
                                            {documentStateFilterOptions.map(
                                                ([value, label]) => (
                                                    <SelectItem
                                                        key={value}
                                                        value={value}
                                                    >
                                                        {label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <button
                                    type="button"
                                    className="text-sm font-medium text-muted-foreground underline-offset-2 hover:underline"
                                    onClick={() =>
                                        applyFilters({
                                            ...emptySelection,
                                            state: '',
                                        })
                                    }
                                >
                                    Clear filters
                                </button>
                            </div>
                        </div>
                    }
                    footer={
                        documents.links.length > 3 ? (
                            <LinkPagination paginator={documents} />
                        ) : undefined
                    }
                >
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead className="text-xs uppercase">
                                    Title
                                </TableHead>
                                <TableHead className="text-xs uppercase">
                                    Path
                                </TableHead>
                                <TableHead className="text-xs uppercase">
                                    Format
                                </TableHead>
                                <TableHead className="text-xs uppercase">
                                    Status
                                </TableHead>
                                <TableHead className="text-xs uppercase">
                                    Version
                                </TableHead>
                                <TableHead className="text-xs uppercase">
                                    Updated
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {documents.data.map((doc) => (
                                <TableRow key={doc.id}>
                                    <TableCell>
                                        <Link
                                            href={`/staff/curriculum/documents/${doc.id}`}
                                            className="font-medium text-primary hover:underline"
                                        >
                                            {doc.title}
                                        </Link>
                                    </TableCell>
                                    <TableCell className="text-xs text-muted-foreground">
                                        {doc.course} / {doc.level} / {doc.unit}
                                        {doc.lesson ? ` / ${doc.lesson}` : ''}
                                    </TableCell>
                                    <TableCell className="text-xs text-muted-foreground uppercase">
                                        {doc.format}
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            className={stateBadgeClass(doc.state)}
                                        >
                                            {doc.state_label}
                                        </span>
                                    </TableCell>
                                    <TableCell className="text-secondary-foreground">
                                        v{doc.version}
                                    </TableCell>
                                    <TableCell className="text-xs text-muted-foreground">
                                        {doc.updated_at}
                                    </TableCell>
                                </TableRow>
                            ))}
                            {documents.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-6 text-center text-sm text-muted-foreground"
                                    >
                                        No documents match the current filters.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </GlcDataTableCard>

                <HierarchyManager tree={tree} />
            </div>
        </GlcLayout>
    );
}
