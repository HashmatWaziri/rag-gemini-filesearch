import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableRow,
} from '@/components/ui/table';
import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import {
    Badge,
    buttonDangerClass,
    buttonPrimaryClass,
    buttonSecondaryClass,
    ConfirmDialog,
    type Option,
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
    guardian_name: string | null;
    guardian_email: string | null;
    requires_guardian_consent: boolean;
    has_guardian_consent: boolean;
    guardian_consent_confirmed_at: string | null;
}

interface UserEditProps {
    user: AdminUser;
    roles: Option[];
    privacyNotice: string;
    status?: string | null;
}

type PendingAction = 'consent' | 'revoke-consent' | 'delete' | 'anonymize';

export default function UserEdit({
    user,
    roles,
    privacyNotice,
    status,
}: UserEditProps) {
    const [pending, setPending] = useState<PendingAction | null>(null);

    const form = useForm<UserFormData>({
        name: user.name,
        email: user.email,
        password: '',
        role: user.role ?? '',
        age: user.age === null ? '' : String(user.age),
        guardian_name: user.guardian_name ?? '',
        guardian_email: user.guardian_email ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(`/admin/users/${user.id}`, { preserveScroll: true });
    };

    const runPending = () => {
        const options = {
            preserveScroll: true,
            onFinish: () => setPending(null),
        };

        switch (pending) {
            case 'consent':
                router.post(`/admin/users/${user.id}/consent`, {}, options);
                break;
            case 'revoke-consent':
                router.delete(`/admin/users/${user.id}/consent`, options);
                break;
            case 'delete':
                router.delete(`/admin/users/${user.id}`, options);
                break;
            case 'anonymize':
                router.post(`/admin/users/${user.id}/anonymize`, {}, options);
                break;
            case null:
                break;
        }
    };

    const dialogCopy: Record<
        PendingAction,
        { title: string; message: string; confirmLabel: string }
    > = {
        consent: {
            title: 'Confirm guardian consent',
            message: `Mark guardian consent as confirmed for ${user.name}? This lets the student use the AI Tutor once a teacher has assigned their course. The action is recorded in the Activity Log.`,
            confirmLabel: 'Confirm consent',
        },
        'revoke-consent': {
            title: 'Remove guardian consent',
            message: `Remove guardian consent for ${user.name}? The student immediately loses access to the AI Tutor until consent is confirmed again. The action is recorded in the Activity Log.`,
            confirmLabel: 'Remove consent',
        },
        delete: {
            title: 'Delete user',
            message: `Permanently delete ${user.name}? Their account is removed and they will no longer be able to log in. This cannot be undone. The action is recorded in the Activity Log.`,
            confirmLabel: 'Delete',
        },
        anonymize: {
            title: 'Remove personal details',
            message: `Replace ${user.name}'s name, email and guardian details with anonymous placeholders? Their test history and chat records are kept, but will no longer show who they belong to. This cannot be undone. The action is recorded in the Activity Log.`,
            confirmLabel: 'Remove details',
        },
    };

    return (
        <GlcLayout title={`Edit user: ${user.name}`}>
            <Head title={`Edit ${user.name}`} />

            <StatusBanner message={status} />

            <div className="mb-4">
                <Link
                    href="/admin/users"
                    className="text-sm font-medium text-primary hover:underline"
                >
                    Back to users
                </Link>
            </div>

            <div className="grid grid-cols-1 gap-5 xl:grid-cols-2 lg:gap-7.5">
                <div className="space-y-5 lg:space-y-7.5">
                    <Card>
                        <CardHeader>
                            <CardTitle>Account details</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <form onSubmit={submit}>
                                <Table className="text-sm">
                                    <TableBody>
                                        <TableRow>
                                            <TableCell
                                                colSpan={3}
                                                className="p-0"
                                            >
                                                <div className="space-y-4 px-5 pb-5">
                                                    <UserFields
                                                        data={form.data}
                                                        setData={form.setData}
                                                        errors={form.errors}
                                                        roles={roles}
                                                        creating={false}
                                                        idPrefix="edit"
                                                    />
                                                    <div className="flex justify-end">
                                                        <button
                                                            type="submit"
                                                            disabled={
                                                                form.processing
                                                            }
                                                            className={
                                                                buttonPrimaryClass
                                                            }
                                                        >
                                                            Save changes
                                                        </button>
                                                    </div>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </form>
                        </CardContent>
                    </Card>

                    <PrivacyNoticeSection text={privacyNotice} />
                </div>

                <div className="space-y-5 lg:space-y-7.5">
                    {user.role === 'student' && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Guardian consent</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {user.requires_guardian_consent ? (
                                    <div className="space-y-3 text-sm">
                                        <div className="flex flex-wrap items-center gap-2">
                                            {user.has_guardian_consent ? (
                                                <>
                                                    <Badge tone="green">
                                                        Consent confirmed
                                                    </Badge>
                                                    <span className="text-muted-foreground">
                                                        {user.guardian_consent_confirmed_at &&
                                                            new Date(
                                                                user.guardian_consent_confirmed_at,
                                                            ).toLocaleString()}
                                                    </span>
                                                </>
                                            ) : (
                                                <Badge tone="amber">
                                                    Consent required
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-secondary-foreground">
                                            Students aged 12-17 cannot use the AI
                                            Tutor and placement results cannot be sent
                                            until an admin confirms guardian consent
                                            (guardian: {user.guardian_name ?? '-'},{' '}
                                            {user.guardian_email ?? '-'}).
                                        </p>
                                        {user.has_guardian_consent ? (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setPending('revoke-consent')
                                                }
                                                className={buttonSecondaryClass}
                                            >
                                                Revoke consent
                                            </button>
                                        ) : (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setPending('consent')
                                                }
                                                className={buttonPrimaryClass}
                                            >
                                                Confirm guardian consent
                                            </button>
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-sm text-secondary-foreground">
                                        Guardian consent is not required for this
                                        student.
                                    </p>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    <Card className="border-destructive/20">
                        <CardHeader>
                            <CardTitle className="text-destructive">
                                Danger zone
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-secondary-foreground">
                                Deleting removes the account permanently — the person
                                can no longer log in. Removing personal details
                                (students only) keeps the student's records but
                                replaces their name, email and guardian details with
                                anonymous placeholders, so the records no longer show
                                who they belong to. Both actions are recorded in the
                                Activity Log and cannot be undone.
                            </p>
                            <div className="mt-4 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    onClick={() => setPending('delete')}
                                    className={buttonDangerClass}
                                >
                                    Delete user
                                </button>
                                {user.role === 'student' && (
                                    <button
                                        type="button"
                                        onClick={() => setPending('anonymize')}
                                        className={buttonSecondaryClass}
                                    >
                                        Remove personal details
                                    </button>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <ConfirmDialog
                open={pending !== null}
                title={pending ? dialogCopy[pending].title : ''}
                message={pending ? dialogCopy[pending].message : ''}
                confirmLabel={pending ? dialogCopy[pending].confirmLabel : ''}
                danger={pending === 'delete' || pending === 'anonymize'}
                onConfirm={runPending}
                onCancel={() => setPending(null)}
            />
        </GlcLayout>
    );
}
