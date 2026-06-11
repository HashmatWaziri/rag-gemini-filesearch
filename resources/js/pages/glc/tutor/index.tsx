import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router } from '@inertiajs/react';

interface Assignment {
    course: string;
    level: string;
    unit: string;
}

interface ConversationItem {
    id: number;
    title: string | null;
    message_count: number;
    last_activity_at: string | null;
}

interface Props {
    assignment: Assignment | null;
    conversations: ConversationItem[];
}

function formatDate(value: string | null): string {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export default function TutorIndex({ assignment, conversations }: Props) {
    if (!assignment) {
        return (
            <GlcLayout title="AI Tutor">
                <Head title="AI Tutor" />
                <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                    <h2 className="text-lg font-semibold text-amber-900">
                        No course assignment yet
                    </h2>
                    <p className="mx-auto mt-2 max-w-md text-sm text-amber-800">
                        The AI Tutor works with the course, level, and unit
                        your teacher assigns to you. Please ask your teacher or
                        a GLC admin to assign your course, then come back here.
                    </p>
                </div>
            </GlcLayout>
        );
    }

    return (
        <GlcLayout title="AI Tutor">
            <Head title="AI Tutor" />

            <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                <p className="font-medium">
                    Your scope: {assignment.course} / {assignment.level} /{' '}
                    {assignment.unit}
                </p>
                <p className="mt-1 text-emerald-800">
                    The tutor replies in English only and helps with Reading,
                    Writing, Grammar, and Vocabulary from your assigned
                    materials.
                </p>
            </div>

            <div className="mb-6 flex flex-col gap-2 sm:flex-row">
                <button
                    type="button"
                    onClick={() => router.post('/tutor/conversations')}
                    className="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500"
                >
                    Start a new conversation
                </button>
                <Link
                    href="/tutor/writing"
                    className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Writing correction
                </Link>
            </div>

            <h2 className="mb-2 text-sm font-semibold tracking-wide text-slate-500 uppercase">
                Previous conversations
            </h2>

            {conversations.length === 0 ? (
                <p className="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-500">
                    No conversations yet. Start one to get help with your
                    lessons.
                </p>
            ) : (
                <ul className="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white">
                    {conversations.map((conversation) => (
                        <li key={conversation.id}>
                            <Link
                                href={`/tutor/conversations/${conversation.id}`}
                                className="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium text-slate-900">
                                        {conversation.title ??
                                            'New conversation'}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {conversation.message_count} messages
                                    </p>
                                </div>
                                <span className="shrink-0 text-xs text-slate-400">
                                    {formatDate(conversation.last_activity_at)}
                                </span>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </GlcLayout>
    );
}
