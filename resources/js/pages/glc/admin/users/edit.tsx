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
            message: `Mark guardian consent as confirmed for ${user.name}? This unlocks the AI Tutor for this student and is recorded in the audit log.`,
            confirmLabel: 'Confirm consent',
        },
        'revoke-consent': {
            title: 'Revoke guardian consent',
            message: `Revoke guardian consent for ${user.name}? The student will lose AI Tutor access until consent is confirmed again.`,
            confirmLabel: 'Revoke consent',
        },
        delete: {
            title: 'Delete user',
            message: `Permanently delete ${user.name}? All account data is removed. This cannot be undone and will be recorded in the audit log.`,
            confirmLabel: 'Delete',
        },
        anonymize: {
            title: 'Anonymize student',
            message: `Replace ${user.name}'s name, email and guardian details with redacted placeholders? Records are kept. This cannot be undone and will be recorded in the audit log.`,
            confirmLabel: 'Anonymize',
        },
    };

    return (
        <GlcLayout title={`Edit user: ${user.name}`}>
            <Head title={`Edit ${user.name}`} />

            <StatusBanner message={status} />

            <div className="mb-4">
                <Link
                    href="/admin/users"
                    className="text-sm font-medium text-emerald-700 hover:underline"
                >
                    Back to users
                </Link>
            </div>

            <div className="space-y-6">
                <form
                    onSubmit={submit}
                    className="space-y-4 rounded-lg border border-slate-200 bg-white p-5"
                >
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
                            disabled={form.processing}
                            className={buttonPrimaryClass}
                        >
                            Save changes
                        </button>
                    </div>
                </form>

                {user.role === 'student' && (
                    <section className="rounded-lg border border-slate-200 bg-white p-5">
                        <h2 className="text-base font-semibold text-slate-900">
                            Guardian consent
                        </h2>

                        {user.requires_guardian_consent ? (
                            <div className="mt-3 space-y-3 text-sm">
                                <div className="flex flex-wrap items-center gap-2">
                                    {user.has_guardian_consent ? (
                                        <>
                                            <Badge tone="green">
                                                Consent confirmed
                                            </Badge>
                                            <span className="text-slate-500">
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
                                <p className="text-slate-600">
                                    Students aged 12-17 cannot use the AI Tutor
                                    and placement results cannot be sent until
                                    an admin confirms guardian consent
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
                                        onClick={() => setPending('consent')}
                                        className={buttonPrimaryClass}
                                    >
                                        Confirm guardian consent
                                    </button>
                                )}
                            </div>
                        ) : (
                            <p className="mt-3 text-sm text-slate-600">
                                Guardian consent is not required for this
                                student.
                            </p>
                        )}
                    </section>
                )}

                <PrivacyNoticeSection text={privacyNotice} />

                <section className="rounded-lg border border-red-200 bg-white p-5">
                    <h2 className="text-base font-semibold text-red-700">
                        Danger zone
                    </h2>
                    <p className="mt-2 text-sm text-slate-600">
                        Deletion permanently removes the account. Anonymization
                        replaces name, email and guardian details with redacted
                        placeholders while keeping records (students only). Both
                        actions are recorded in the audit log.
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
                                Anonymize student
                            </button>
                        )}
                    </div>
                </section>
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
