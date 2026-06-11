import GlcLayout from '@/layouts/glc-layout';
import { Head, Link } from '@inertiajs/react';

interface ConversationRow {
    id: number;
    title: string | null;
    message_count: number;
    last_activity_at: string | null;
}

interface SubmissionRow {
    id: number;
    status: string;
    excerpt: string;
    created_at: string | null;
}

interface ViolationRow {
    id: number;
    category: string;
    category_label: string;
    excerpt: string | null;
    occurred_at: string;
}

interface Props {
    student: { id: number; name: string; email: string };
    activity: { conversation_count: number; last_active_at: string | null };
    conversations: ConversationRow[];
    writingSubmissions: SubmissionRow[];
    violations: ViolationRow[];
}

function formatDateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '';
}

const SUBMISSION_STATUS_LABELS: Record<string, string> = {
    completed: 'Feedback ready',
    pending: 'Being checked',
    failed: 'Could not be checked',
};

export default function StaffTutorStudent({
    student,
    activity,
    conversations,
    writingSubmissions,
    violations,
}: Props) {
    return (
        <GlcLayout title={student.name}>
            <Head title={`Tutor activity - ${student.name}`} />

            <Link
                href="/staff/tutor"
                className="mb-4 inline-block text-sm font-medium text-emerald-700 hover:underline"
            >
                Back to Tutor Activity
            </Link>

            <div className="mb-6 grid grid-cols-2 gap-3">
                <div className="rounded-lg border border-slate-200 bg-white p-4">
                    <p className="text-xs text-slate-500">Conversations</p>
                    <p className="text-xl font-semibold text-slate-900">
                        {activity.conversation_count}
                    </p>
                </div>
                <div className="rounded-lg border border-slate-200 bg-white p-4">
                    <p className="text-xs text-slate-500">Last active</p>
                    <p className="text-sm font-semibold text-slate-900">
                        {activity.last_active_at
                            ? new Date(
                                  activity.last_active_at,
                              ).toLocaleDateString()
                            : 'Never'}
                    </p>
                </div>
            </div>

            <section className="mb-6">
                <h2 className="mb-2 text-sm font-semibold tracking-wide text-slate-500 uppercase">
                    Conversations
                </h2>
                {conversations.length === 0 ? (
                    <p className="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-500">
                        No conversations.
                    </p>
                ) : (
                    <ul className="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white">
                        {conversations.map((conversation) => (
                            <li key={conversation.id}>
                                <Link
                                    href={`/staff/tutor/conversations/${conversation.id}`}
                                    className="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-slate-900">
                                            {conversation.title ??
                                                'Untitled conversation'}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            {conversation.message_count}{' '}
                                            messages
                                        </p>
                                    </div>
                                    <span className="shrink-0 text-xs text-slate-400">
                                        {formatDateTime(
                                            conversation.last_activity_at,
                                        )}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <section className="mb-6">
                <h2 className="mb-2 text-sm font-semibold tracking-wide text-slate-500 uppercase">
                    Writing submissions
                </h2>
                {writingSubmissions.length === 0 ? (
                    <p className="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-500">
                        No writing submissions.
                    </p>
                ) : (
                    <ul className="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white">
                        {writingSubmissions.map((submission) => (
                            <li key={submission.id}>
                                <Link
                                    href={`/staff/tutor/writing/${submission.id}`}
                                    className="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50"
                                >
                                    <p className="min-w-0 truncate text-sm text-slate-900">
                                        {submission.excerpt}
                                    </p>
                                    <span className="shrink-0 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                        {SUBMISSION_STATUS_LABELS[
                                            submission.status
                                        ] ?? submission.status}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <section>
                <h2 className="mb-2 text-sm font-semibold tracking-wide text-slate-500 uppercase">
                    Needs attention
                </h2>
                {violations.length === 0 ? (
                    <p className="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-500">
                        Nothing needs attention.
                    </p>
                ) : (
                    <ul className="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white">
                        {violations.map((violation) => (
                            <li key={violation.id} className="px-4 py-3">
                                <div className="flex items-center justify-between gap-3">
                                    <span className="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">
                                        {violation.category_label}
                                    </span>
                                    <span className="text-xs text-slate-400">
                                        {formatDateTime(violation.occurred_at)}
                                    </span>
                                </div>
                                {violation.excerpt && (
                                    <p className="mt-1.5 text-sm text-slate-600">
                                        &quot;{violation.excerpt}&quot;
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </GlcLayout>
    );
}
