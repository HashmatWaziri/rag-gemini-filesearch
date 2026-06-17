import GlcLayout from '@/layouts/glc-layout';
import { Head, useForm } from '@inertiajs/react';
import { useMemo, type FormEvent } from 'react';
import {
    buttonPrimaryClass,
    StatusBanner,
} from '../components';

interface PermissionRow {
    key: string;
    label: string;
    group: string;
    group_label: string;
}

interface RoleColumn {
    key: string;
    label: string;
}

interface PermissionsIndexProps {
    permissions: PermissionRow[];
    roles: RoleColumn[];
    matrix: Record<string, string[]>;
    status?: string | null;
}

function buildMatrixState(
    roles: RoleColumn[],
    permissions: PermissionRow[],
    matrix: Record<string, string[]>,
): Record<string, Record<string, boolean>> {
    const state: Record<string, Record<string, boolean>> = {};

    for (const role of roles) {
        const granted = new Set(matrix[role.key] ?? []);
        state[role.key] = {};

        for (const permission of permissions) {
            state[role.key][permission.key] = granted.has(permission.key);
        }
    }

    return state;
}

export default function PermissionsIndex({
    permissions,
    roles,
    matrix,
    status,
}: PermissionsIndexProps) {
    const initialMatrix = useMemo(
        () => buildMatrixState(roles, permissions, matrix),
        [roles, permissions, matrix],
    );

    const form = useForm({ matrix: initialMatrix });

    const groups = useMemo(() => {
        const grouped = new Map<string, { label: string; items: PermissionRow[] }>();

        for (const permission of permissions) {
            const existing = grouped.get(permission.group);

            if (existing) {
                existing.items.push(permission);
                continue;
            }

            grouped.set(permission.group, {
                label: permission.group_label,
                items: [permission],
            });
        }

        return [...grouped.entries()].map(([key, value]) => ({
            key,
            ...value,
        }));
    }, [permissions]);

    const toggle = (roleKey: string, permissionKey: string, checked: boolean) => {
        form.setData('matrix', {
            ...form.data.matrix,
            [roleKey]: {
                ...form.data.matrix[roleKey],
                [permissionKey]: checked,
            },
        });
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put('/admin/permissions', { preserveScroll: true });
    };

    return (
        <GlcLayout title="Roles & Permissions">
            <Head title="Roles & Permissions" />

            <StatusBanner message={status} />

            <div className="mb-6 max-w-3xl">
                <h1 className="text-xl font-semibold text-mono">
                    Roles & Permissions
                </h1>
                <p className="mt-1 text-sm text-secondary-foreground">
                    Control which GLC roles can perform curriculum actions.
                    Changes apply immediately for all users with that role.
                </p>
            </div>

            <form onSubmit={submit} className="space-y-8">
                {groups.map((group) => (
                    <section
                        key={group.key}
                        className="overflow-hidden rounded-lg border border-border bg-card"
                    >
                        <div className="border-b border-border bg-muted/30 px-4 py-3">
                            <h2 className="text-base font-semibold text-mono">
                                {group.label}
                            </h2>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead>
                                    <tr className="border-b border-border text-left">
                                        <th className="px-4 py-3 font-medium text-secondary-foreground">
                                            Permission
                                        </th>
                                        {roles.map((role) => (
                                            <th
                                                key={role.key}
                                                className="px-4 py-3 text-center font-medium text-secondary-foreground"
                                            >
                                                {role.label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {group.items.map((permission) => (
                                        <tr key={permission.key}>
                                            <td className="px-4 py-3 text-foreground">
                                                {permission.label}
                                            </td>
                                            {roles.map((role) => (
                                                <td
                                                    key={`${role.key}-${permission.key}`}
                                                    className="px-4 py-3 text-center"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={
                                                            form.data.matrix[
                                                                role.key
                                                            ]?.[permission.key] ??
                                                            false
                                                        }
                                                        onChange={(e) =>
                                                            toggle(
                                                                role.key,
                                                                permission.key,
                                                                e.target.checked,
                                                            )
                                                        }
                                                        aria-label={`${role.label}: ${permission.label}`}
                                                        className="size-4 rounded border-input text-primary focus-visible:ring-ring/50"
                                                    />
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                ))}

                <div className="flex justify-end">
                    <button
                        type="submit"
                        disabled={form.processing}
                        className={buttonPrimaryClass}
                    >
                        Save permissions
                    </button>
                </div>
            </form>
        </GlcLayout>
    );
}
