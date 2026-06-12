export interface GlcNavItem {
    label: string;
    href: string;
}

export interface GlcNavSection {
    title: string;
    items: GlcNavItem[];
}

export type GlcUserRole =
    | 'admin'
    | 'academic_supervisor'
    | 'teacher'
    | 'student';

export interface GlcUser {
    name: string;
    email: string;
    role: GlcUserRole | null;
}

const PLACEMENT: GlcNavItem[] = [
    { label: 'Placement Tests', href: '/staff/review' },
];

const STUDENTS_TUTOR: GlcNavItem[] = [
    { label: 'My Students', href: '/staff/students' },
    { label: 'Tutor Activity', href: '/staff/tutor' },
];

const CONTENT: GlcNavItem[] = [
    { label: 'Curriculum', href: '/staff/curriculum' },
    { label: 'Placement Test Content', href: '/staff/placement-content' },
];

const ADMINISTRATION: GlcNavItem[] = [
    { label: 'Users', href: '/admin/users' },
    { label: 'Access Codes', href: '/admin/access-codes' },
    { label: 'Exports', href: '/admin/exports' },
    { label: 'Activity Log', href: '/admin/audit' },
    { label: 'Settings', href: '/admin/settings' },
];

export const STUDENT_NAV: GlcNavItem[] = [
    { label: 'AI Tutor', href: '/tutor' },
];

export const ROLE_LABELS: Record<GlcUserRole, string> = {
    admin: 'Admin',
    academic_supervisor: 'Academic Supervisor',
    teacher: 'Teacher',
    student: 'Student',
};

function staffSections(role: GlcUserRole): GlcNavSection[] {
    const sections: GlcNavSection[] = [
        { title: 'Placement', items: PLACEMENT },
        { title: 'Students & Tutor', items: STUDENTS_TUTOR },
    ];

    if (role === 'academic_supervisor' || role === 'admin') {
        sections.push({ title: 'Content', items: CONTENT });
    }

    if (role === 'admin') {
        sections.push({ title: 'Administration', items: ADMINISTRATION });
    }

    return sections;
}

/** Grouped nav sections for staff roles; empty for students (flat links instead). */
export function getNavSectionsForRole(
    role: GlcUserRole | null,
): GlcNavSection[] {
    if (!role || role === 'student') {
        return [];
    }

    return staffSections(role);
}

/** Flat nav list preserving pinned hrefs and role visibility. */
export function getFlatNavItems(role: GlcUserRole | null): GlcNavItem[] {
    if (!role) {
        return [];
    }

    if (role === 'student') {
        return STUDENT_NAV;
    }

    return staffSections(role).flatMap((section) => section.items);
}

export function isNavPathActive(currentPath: string, href: string): boolean {
    return (
        currentPath === href ||
        (href.length > 1 && currentPath.startsWith(href))
    );
}

export function hasActiveChild(
    currentPath: string,
    items: GlcNavItem[],
): boolean {
    return items.some((item) => isNavPathActive(currentPath, item.href));
}

export function getInitials(name: string): string {
    return name
        .split(/\s+/)
        .map((part) => part[0] ?? '')
        .join('')
        .slice(0, 2)
        .toUpperCase();
}
