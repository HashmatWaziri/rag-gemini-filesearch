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

export type DocumentState =
    | 'draft'
    | 'publishing'
    | 'published'
    | 'publish_failed'
    | 'archived';

export interface DocumentRow {
    id: number;
    title: string;
    course: string;
    level: string;
    unit: string;
    lesson: string | null;
    format: string;
    status: string;
    index_status: string;
    state: DocumentState;
    state_label: string;
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

export const documentStateFilterOptions: [DocumentState, string][] = [
    ['draft', 'Draft'],
    ['publishing', 'Being prepared'],
    ['published', 'Live for students'],
    ['publish_failed', "Couldn't be published"],
    ['archived', 'Archived'],
];
