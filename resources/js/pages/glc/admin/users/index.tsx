import { GlcDataTableCard, GlcSearchInput } from '@/components/glc';
import { MetronicSelect } from '@/components/glc/metronic-select';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import {
    Badge,
    ConfirmDialog,
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
                <div className="mb-4 rounded-xl border border-border bg-card px-5 py-4 text-sm">
                    <p className="font-medium text-mono">
                        Import finished: {importResult.created}{' '}
                        {importResult.created === 1 ? 'user' : 'users'} added,{' '}
                        {importResult.errors.length}{' '}
                        {importResult.errors.length === 1 ? 'row' : 'rows'} with
                        problems.
                    </p>
                    {importResult.errors.length > 0 && (
                        <ul className="mt-2 space-y-1 text-destructive">
                            {importResult.errors.map((error) => (
                                <li key={`${error.row}-${error.message}`}>
                                    Row {error.row}: {error.message}
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}

            <GlcDataTableCard
                filters={
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyFilters();
                        }}
                        className="flex flex-wrap items-center gap-2.5"
                    >
                        <GlcSearchInput
                            value={search}
                            onValueChange={setSearch}
                            placeholder="Search name or email"
                            aria-label="Search users"
                            inputClassName="sm:w-52"
                        />
                        <MetronicSelect
                            className="w-40"
                            aria-label="Filter by role"
                            value={filters.role}
                            onChange={(value) =>
                                applyFilters({ role: value ?? undefined })
                            }
                            options={roles.map((role) => ({
                                value: role.value,
                                label: role.label,
                            }))}
                            placeholder="All roles"
                            isSearchable={false}
                        />
                        <Button type="submit" variant="outline">
                            Search
                        </Button>
                    </form>
                }
                actions={
                    <>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setImportOpen(true)}
                        >
                            Bulk import
                        </Button>
                        <Button type="button" onClick={() => setCreateOpen(true)}>
                            New user
                        </Button>
                    </>
                }
                footer={<Pagination paginator={users} />}
            >
                <Table>
                    <TableHeader>
                        <TableRow className="bg-muted/50 hover:bg-muted/50">
                            <TableHead className="text-xs uppercase">
                                Name
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                Role
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                Age
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                Guardian consent
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                <span className="sr-only">Actions</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {users.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No users found.
                                </TableCell>
                            </TableRow>
                        )}
                        {users.data.map((user) => (
                            <TableRow key={user.id}>
                                <TableCell>
                                    <p className="font-medium text-mono">
                                        {user.name}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {user.email}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <Badge tone={roleTone(user.role)}>
                                        {user.role_label ?? 'None'}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-secondary-foreground">
                                    {user.age ?? '-'}
                                </TableCell>
                                <TableCell>
                                    {user.requires_guardian_consent ? (
                                        user.has_guardian_consent ? (
                                            <Badge tone="green">
                                                Confirmed
                                            </Badge>
                                        ) : (
                                            <Badge tone="amber">Required</Badge>
                                        )
                                    ) : (
                                        <span className="text-muted-foreground">
                                            -
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell>
                                    <div className="flex justify-end gap-2">
                                        <Link
                                            href={`/admin/users/${user.id}/edit`}
                                            className="text-sm font-medium text-primary hover:underline"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() => setDeleting(user)}
                                            className="text-sm font-medium text-destructive hover:underline"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </GlcDataTableCard>

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
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setCreateOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={createForm.processing}>
                            Create user
                        </Button>
                    </div>
                </form>
            </Modal>

            <Modal
                open={importOpen}
                title="Bulk import users"
                onClose={() => setImportOpen(false)}
            >
                <form onSubmit={submitImport} className="space-y-4">
                    <p className="text-sm text-secondary-foreground">
                        Upload a CSV with the columns{' '}
                        <code className="rounded bg-accent px-1 text-xs">
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
                        className="block w-full text-sm text-secondary-foreground file:mr-3 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary"
                    />
                    {importForm.errors.file && (
                        <p className="text-xs text-destructive">
                            {importForm.errors.file}
                        </p>
                    )}

                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setImportOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={
                                importForm.processing || !importForm.data.file
                            }
                        >
                            Import
                        </Button>
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
