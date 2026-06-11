export interface TreeLesson {
    id: number;
    name: string;
    position: number;
}

export interface TreeUnit {
    id: number;
    name: string;
    position: number;
    lessons: TreeLesson[];
}

export interface TreeLevel {
    id: number;
    name: string;
    position: number;
    units: TreeUnit[];
}

export interface TreeCourse {
    id: number;
    name: string;
    levels: TreeLevel[];
}

export interface DocumentRow {
    id: number;
    title: string;
    course: string;
    level: string;
    unit: string;
    lesson: string | null;
    format: string;
    status: string;
    status_label: string;
    index_status: string;
    index_status_label: string;
    version: number;
    updated_at: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    total: number;
    from: number | null;
    to: number | null;
}

export interface BulkReportRow {
    filename: string;
    success: boolean;
    error: string | null;
    document_id: number | null;
}

export interface UploadConfig {
    allowedExtensions: string[];
    maxFileSizeKb: number;
    maxBulkFiles: number;
}

export interface HierarchySelection {
    course_id: string;
    course_level_id: string;
    course_unit_id: string;
    course_lesson_id: string;
}

export const emptySelection: HierarchySelection = {
    course_id: '',
    course_level_id: '',
    course_unit_id: '',
    course_lesson_id: '',
};

export const inputClass =
    'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500';

export const labelClass = 'mb-1 block text-xs font-medium text-slate-600';

export const primaryButtonClass =
    'rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-50';

export const secondaryButtonClass =
    'rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50';

export const dangerButtonClass =
    'rounded-md border border-red-200 bg-white px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50';

const statusBadgeColors: Record<string, string> = {
    draft: 'bg-slate-100 text-slate-700',
    published: 'bg-emerald-100 text-emerald-700',
    archived: 'bg-amber-100 text-amber-700',
};

const indexBadgeColors: Record<string, string> = {
    pending: 'bg-slate-100 text-slate-600',
    indexing: 'bg-blue-100 text-blue-700',
    indexed: 'bg-emerald-100 text-emerald-700',
    failed: 'bg-red-100 text-red-700',
    removed: 'bg-slate-100 text-slate-500',
};

export function statusBadgeClass(status: string): string {
    return `inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${statusBadgeColors[status] ?? 'bg-slate-100 text-slate-600'}`;
}

export function indexBadgeClass(indexStatus: string): string {
    return `inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${indexBadgeColors[indexStatus] ?? 'bg-slate-100 text-slate-600'}`;
}
