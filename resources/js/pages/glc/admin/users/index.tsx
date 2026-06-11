import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import {
    Badge,
    buttonPrimaryClass,
    buttonSecondaryClass,
    ConfirmDialog,
    inputClass,
    Modal,
    type Option,
    Pagination,
    type Paginator,
    PrivacyNoticeSection,
    StatusBanner,
} from '../components';
import { UserFields, type UserFormData } from './user-fields';

interface AdminUser {
    id: number;
    name: string;
    email: string;
    role: string | null;
    role_label: string | null;
    age: number | null;
    requires_guardian_consent: boolean;
    has_guardian_consent: boolean;
}

interface ImportResult {
    created: number;
    errors: { row: number; message: string }[];
}

interface UsersIndexProps {
    users: Paginator<AdminUser>;
    filters: { role: string | null; search: string };
    roles: Option[];
    privacyNotice: string;
    status?: string | null;
    importResult?: ImportResult | null;
}

const EMPTY_USER: UserFormData = {
    name: '',
    email: '',
    password: '',
    role: '',
    age: '',
    guardian_name: '',
    guardian_email: '',
};

function roleTone(role: string | null): 'green' | 'blue' | 'amber' | 'slate' {
    switch (role) {
        case 'admin':
            return 'green';
        case 'academic_supervisor':
            return 'blue';
        case 'teacher':
            return 'amber';
        default:
            return 'slate';
    }
}

export default function UsersIndex({
    users,
    filters,
    roles,
    privacyNotice,
    status,
    importResult,
}: UsersIndexProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [createOpen, setCreateOpen] = useState(false);
    const [importOpen, setImportOpen] = useState(false);
    const [deleting, setDeleting] = useState<AdminUser | null>(null);

    const createForm = useForm<UserFormData>({ ...EMPTY_USER });
    const importForm = useForm<{ file: File | null }>({ file: null });

    const applyFilters = (overrides: Partial<{ role: string }> = {}) => {
        router.get(
            '/admin/users',
            {
                search: search || undefined,
                role: overrides.role ?? filters.role ?? undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const submitCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.post('/admin/users', {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                setCreateOpen(false);
            },
        });
    };

    const submitImport = (e: FormEvent) => {
        e.preventDefault();
        importForm.post('/admin/users/import', {
            forceFormData: true,
            onSuccess: () => {
                importForm.reset();
                setImportOpen(false);
            },
        });
    };

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        router.delete(`/admin/users/${deleting.id}`, {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <GlcLayout title="Users">
            <Head title="Users" />

            <StatusBanner message={status} />

            {importResult && (
                <div className="mb-4 rounded-md border border-slate-200 bg-white p-4 text-sm">
                    <p className="font-medium text-slate-800">
                        Import finished: {importResult.created}{' '}
                        {importResult.created === 1 ? 'user' : 'users'} added,{' '}
                        {importResult.errors.length}{' '}
                        {importResult.errors.length === 1 ? 'row' : 'rows'} with
                        problems.
                    </p>
                    {importResult.errors.length > 0 && (
                        <ul className="mt-2 space-y-1 text-red-700">
                            {importResult.errors.map((error) => (
                                <li key={`${error.row}-${error.message}`}>
                                    Row {error.row}: {error.message}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}

            <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        applyFilters();
                    }}
                    className="flex flex-1 flex-col gap-2 sm:flex-row"
                >
                    <input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search name or email"
                        aria-label="Search users"
                        className={`${inputClass} sm:max-w-xs`}
                    />
                    <select
                        value={filters.role ?? ''}
                        onChange={(e) =>
                            applyFilters({ role: e.target.value || undefined })
                        }
                        aria-label="Filter by role"
                        className={`${inputClass} sm:max-w-44`}
                    >
                        <option value="">All roles</option>
                        {roles.map((role) => (
                            <option key={role.value} value={role.value}>
                                {role.label}
                            </option>
                        ))}
                    </select>
                    <button type="submit" className={buttonSecondaryClass}>
                        Search
                    </button>
                </form>

                <div className="flex gap-2">
                    <button
                        type="button"
                        onClick={() => setImportOpen(true)}
                        className={buttonSecondaryClass}
                    >
                        Bulk import
                    </button>
                    <button
                        type="button"
                        onClick={() => setCreateOpen(true)}
                        className={buttonPrimaryClass}
                    >
                        New user
                    </button>
                </div>
            </div>

            <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table className="w-full min-w-[640px] text-left text-sm">
                    <thead className="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase">
                        <tr>
                            <th className="px-4 py-3">Name</th>
                            <th className="px-4 py-3">Role</th>
                            <th className="px-4 py-3">Age</th>
                            <th className="px-4 py-3">Guardian consent</th>
                            <th className="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                        {users.data.length === 0 && (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="px-4 py-8 text-center text-slate-500"
                                >
                                    No users found.
                                </td>
                            </tr>
                        )}
                        {users.data.map((user) => (
                            <tr key={user.id}>
                                <td className="px-4 py-3">
                                    <p className="font-medium text-slate-900">
                                        {user.name}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {user.email}
                                    </p>
                                </td>
                                <td className="px-4 py-3">
                                    <Badge tone={roleTone(user.role)}>
                                        {user.role_label ?? 'None'}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3 text-slate-600">
                                    {user.age ?? '-'}
                                </td>
                                <td className="px-4 py-3">
                                    {user.requires_guardian_consent ? (
                                        user.has_guardian_consent ? (
                                            <Badge tone="green">
                                                Confirmed
                                            </Badge>
                                        ) : (
                                            <Badge tone="amber">Required</Badge>
                                        )
                                    ) : (
                                        <span className="text-slate-400">
                                            -
                                        </span>
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex justify-end gap-2">
                                        <Link
                                            href={`/admin/users/${user.id}/edit`}
                                            className="text-sm font-medium text-emerald-700 hover:underline"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() => setDeleting(user)}
                                            className="text-sm font-medium text-red-600 hover:underline"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Pagination paginator={users} />

            <Modal
                open={createOpen}
                title="Create user"
                onClose={() => setCreateOpen(false)}
            >
                <form onSubmit={submitCreate} className="space-y-4">
                    <UserFields
                        data={createForm.data}
                        setData={createForm.setData}
                        errors={createForm.errors}
                        roles={roles}
                        creating
                        idPrefix="create"
                    />

                    <PrivacyNoticeSection text={privacyNotice} />

                    <div className="flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={() => setCreateOpen(false)}
                            className={buttonSecondaryClass}
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={createForm.processing}
                            className={buttonPrimaryClass}
                        >
                            Create user
                        </button>
                    </div>
                </form>
            </Modal>

            <Modal
                open={importOpen}
                title="Bulk import users"
                onClose={() => setImportOpen(false)}
            >
                <form onSubmit={submitImport} className="space-y-4">
                    <p className="text-sm text-slate-600">
                        Upload a CSV with the columns{' '}
                        <code className="rounded bg-slate-100 px-1 text-xs">
                            name,email,password,role,age,guardian_name,guardian_email
                        </code>
                        . Each row is checked separately: rows that pass are
                        added straight away, and rows with problems are listed
                        with their row number so you can fix and re-upload them.
                    </p>

                    <input
                        type="file"
                        accept=".csv,text/csv"
                        aria-label="CSV file"
                        onChange={(e) =>
                            importForm.setData(
                                'file',
                                e.target.files?.[0] ?? null,
                            )
                        }
                        className="block w-full text-sm text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-emerald-700"
                    />
                    {importForm.errors.file && (
                        <p className="text-xs text-red-600">
                            {importForm.errors.file}
                        </p>
                    )}

                    <div className="flex justify-end gap-2">
                        <button
                            type="button"
                            onClick={() => setImportOpen(false)}
                            className={buttonSecondaryClass}
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={
                                importForm.processing || !importForm.data.file
                            }
                            className={buttonPrimaryClass}
                        >
                            Import
                        </button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                open={deleting !== null}
                title="Delete user"
                message={`Permanently delete ${deleting?.name ?? ''}? Their account is removed and they will no longer be able to log in. This cannot be undone. The action is recorded in the Activity Log.`}
                confirmLabel="Delete"
                danger
                onConfirm={confirmDelete}
                onCancel={() => setDeleting(null)}
            />
        </GlcLayout>
    );
}
