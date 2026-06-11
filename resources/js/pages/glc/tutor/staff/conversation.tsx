import GlcLayout from '@/layouts/glc-layout';
import { Head, Link } from '@inertiajs/react';

interface MessageRow {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    rotated: boolean;
    violation: string | null;
    created_at: string | null;
}

interface Props {
    student: { id: number; name: string };
    conversation: {
        id: number;
        title: string | null;
        summary: string | null;
        last_activity_at: string | null;
    };
    messages: MessageRow[];
}

export default function StaffTutorConversation({
    student,
    conversation,
    messages,
}: Props) {
    return (
        <GlcLayout title={conversation.title ?? 'Conversation'}>
            <Head title={`Transcript - ${student.name}`} />

            <Link
                href={`/staff/tutor/students/${student.id}`}
                className="mb-4 inline-block text-sm font-medium text-emerald-700 hover:underline"
            >
                Back to {student.name}
            </Link>

            {conversation.summary && (
                <div className="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p className="mb-1 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        Summary of earlier messages
                    </p>
                    <p className="text-sm whitespace-pre-wrap text-slate-700">
                        {conversation.summary}
                    </p>
                </div>
            )}

            <div className="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
                {messages.length === 0 && (
                    <p className="py-6 text-center text-sm text-slate-400">
                        No messages in this conversation.
                    </p>
                )}

                {messages.map((message) => (
                    <div
                        key={message.id}
                        className={`flex ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
                    >
                        <div
                            className={`max-w-[85%] rounded-2xl px-4 py-2.5 text-sm whitespace-pre-wrap sm:max-w-[75%] ${
                                message.role === 'user'
                                    ? 'rounded-br-sm bg-emerald-600 text-white'
                                    : 'rounded-bl-sm bg-slate-100 text-slate-900'
                            } ${message.rotated ? 'opacity-70' : ''}`}
                        >
                            {message.content}
                            <div className="mt-1.5 flex flex-wrap items-center gap-2">
                                {message.rotated && (
                                    <span
                                        className={`rounded-full px-2 py-0.5 text-[10px] font-medium ${
                                            message.role === 'user'
                                                ? 'bg-emerald-700 text-emerald-100'
                                                : 'bg-slate-200 text-slate-600'
                                        }`}
                                    >
                                        Covered by the summary above
                                    </span>
                                )}
                                {message.violation && (
                                    <span className="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-medium text-red-700">
                                        Needs attention: {message.violation}
                                    </span>
                                )}
                                {message.created_at && (
                                    <span
                                        className={`text-[10px] ${
                                            message.role === 'user'
                                                ? 'text-emerald-100'
                                                : 'text-slate-400'
                                        }`}
                                    >
                                        {new Date(
                                            message.created_at,
                                        ).toLocaleString()}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </GlcLayout>
    );
}
