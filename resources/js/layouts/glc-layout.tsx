import { Link, usePage } from '@inertiajs/react';
import { type ReactNode, useState } from 'react';

interface GlcUser {
    name: string;
    email: string;
    role: 'admin' | 'academic_supervisor' | 'teacher' | 'student' | null;
}

interface NavItem {
    label: string;
    href: string;
}

const NAV_BY_ROLE: Record<string, NavItem[]> = {
    student: [{ label: 'AI Tutor', href: '/tutor' }],
    teacher: [
        { label: 'Placement Tests', href: '/staff/review' },
        { label: 'My Students', href: '/staff/students' },
        { label: 'Tutor Activity', href: '/staff/tutor' },
    ],
    academic_supervisor: [
        { label: 'Placement Tests', href: '/staff/review' },
        { label: 'My Students', href: '/staff/students' },
        { label: 'Tutor Activity', href: '/staff/tutor' },
        { label: 'Curriculum', href: '/staff/curriculum' },
        { label: 'Placement Test Content', href: '/staff/placement-content' },
    ],
    admin: [
        { label: 'Placement Tests', href: '/staff/review' },
        { label: 'My Students', href: '/staff/students' },
        { label: 'Tutor Activity', href: '/staff/tutor' },
        { label: 'Curriculum', href: '/staff/curriculum' },
        { label: 'Placement Test Content', href: '/staff/placement-content' },
        { label: 'Users', href: '/admin/users' },
        { label: 'Access Codes', href: '/admin/access-codes' },
        { label: 'Exports', href: '/admin/exports' },
        { label: 'Activity Log', href: '/admin/audit' },
        { label: 'Settings', href: '/admin/settings' },
    ],
};

interface GlcLayoutProps {
    children: ReactNode;
    title?: string;
}

/**
 * Mobile-first shell for all GLC platform pages (staff, student, admin).
 * Placement candidate pages use their own minimal chrome instead.
 */
export default function GlcLayout({ children, title }: GlcLayoutProps) {
    const page = usePage<{ auth: { user: GlcUser | null } }>();
    const user = page.props.auth?.user ?? null;
    const [menuOpen, setMenuOpen] = useState(false);
    const navItems = user?.role ? (NAV_BY_ROLE[user.role] ?? []) : [];
    const currentPath =
        typeof window !== 'undefined' ? window.location.pathname : '';

    return (
        <div className="flex min-h-screen flex-col bg-slate-50 text-slate-900">
            <header className="sticky top-0 z-40 border-b border-slate-200 bg-white">
                <div className="mx-auto flex h-14 max-w-5xl items-center justify-between gap-3 px-4">
                    <Link
                        href={user?.role ? '/dashboard' : '/'}
                        className="flex items-center gap-2 font-semibold"
                    >
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-600 text-sm font-bold text-white">
                            GLC
                        </span>
                        <span className="hidden text-sm sm:inline">
                            Greats Language Center
                        </span>
                    </Link>

                    {user && (
                        <button
                            type="button"
                            onClick={() => setMenuOpen(!menuOpen)}
                            className="rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium md:hidden"
                            aria-expanded={menuOpen}
                            aria-label="Toggle navigation"
                        >
                            Menu
                        </button>
                    )}

                    {user && (
                        <nav className="hidden items-center gap-1 md:flex">
                            {navItems.map((item) => (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className={`rounded-md px-3 py-1.5 text-sm font-medium transition-colors ${
                                        currentPath.startsWith(item.href)
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'text-slate-600 hover:bg-slate-100'
                                    }`}
                                >
                                    {item.label}
                                </Link>
                            ))}
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                className="ml-2 rounded-md px-3 py-1.5 text-sm font-medium text-slate-500 hover:bg-slate-100"
                            >
                                Log out
                            </Link>
                        </nav>
                    )}
                </div>

                {user && menuOpen && (
                    <nav className="border-t border-slate-200 bg-white px-4 py-2 md:hidden">
                        {navItems.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`block rounded-md px-3 py-2 text-sm font-medium ${
                                    currentPath.startsWith(item.href)
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'text-slate-600'
                                }`}
                                onClick={() => setMenuOpen(false)}
                            >
                                {item.label}
                            </Link>
                        ))}
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="mt-1 block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-slate-500"
                        >
                            Log out
                        </Link>
                    </nav>
                )}
            </header>

            <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-6">
                {title && (
                    <h1 className="mb-4 text-xl font-semibold tracking-tight">
                        {title}
                    </h1>
                )}
                {children}
            </main>

            <footer className="border-t border-slate-200 bg-white py-4 text-center text-xs text-slate-400">
                Greats Language Center — GLC AI Platform
            </footer>
        </div>
    );
}
