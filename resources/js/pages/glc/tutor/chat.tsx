import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, type FormEvent } from 'react';

interface Assignment {
    course: string;
    level: string;
    unit: string;
}

interface Message {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    citations: string[];
}

interface ConversationItem {
    id: number;
    title: string | null;
    message_count: number;
    last_activity_at: string | null;
}

interface Props {
    conversation: { id: number; title: string | null };
    messages: Message[];
    conversations: ConversationItem[];
    assignment: Assignment;
    materialsReady: boolean;
}

export default function TutorChat({
    conversation,
    messages,
    conversations,
    assignment,
    materialsReady,
}: Props) {
    const { data, setData, post, processing, reset } = useForm({
        message: '',
    });
    const bottomRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ block: 'end' });
    }, [messages.length, processing]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!data.message.trim() || processing) {
            return;
        }

        post(`/tutor/conversations/${conversation.id}/messages`, {
            preserveScroll: true,
            onSuccess: () => reset('message'),
        });
    };

    return (
        <GlcLayout>
            <Head title={conversation.title ?? 'AI Tutor'} />

            <div className="flex gap-6">
                <aside className="hidden w-56 shrink-0 md:block">
                    <button
                        type="button"
                        onClick={() => router.post('/tutor/conversations')}
                        className="mb-3 w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                    >
                        New conversation
                    </button>
                    <ul className="space-y-1">
                        {conversations.map((item) => (
                            <li key={item.id}>
                                <Link
                                    href={`/tutor/conversations/${item.id}`}
                                    className={`block truncate rounded-md px-3 py-2 text-sm ${
                                        item.id === conversation.id
                                            ? 'bg-emerald-50 font-medium text-emerald-700'
                                            : 'text-slate-600 hover:bg-slate-100'
                                    }`}
                                >
                                    {item.title ?? 'New conversation'}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </aside>

                <div className="flex min-h-[70vh] flex-1 flex-col">
                    <div className="mb-3 flex items-center justify-between gap-2">
                        <Link
                            href="/tutor"
                            className="text-sm font-medium text-emerald-700 hover:underline md:hidden"
                        >
                            Back to conversations
                        </Link>
                        <p className="truncate text-xs text-slate-500">
                            {assignment.course} / {assignment.level} /{' '}
                            {assignment.unit}
                        </p>
                    </div>

                    <p className="mb-3 rounded-md bg-slate-100 px-3 py-2 text-xs text-slate-600">
                        The tutor replies in English only. It guides your
                        homework with hints and explanations, and does not give
                        direct answers.
                    </p>

                    <div className="flex-1 space-y-3 overflow-y-auto rounded-lg border border-slate-200 bg-white p-4">
                        {messages.length === 0 && !processing && (
                            <p className="py-8 text-center text-sm text-slate-400">
                                Ask anything about your Reading, Writing,
                                Grammar, or Vocabulary lessons.
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
                                    }`}
                                >
                                    {message.content}
                                    {message.citations.length > 0 && (
                                        <div className="mt-2 space-y-0.5 border-t border-slate-200 pt-1.5 text-xs text-slate-500">
                                            {message.citations.map(
                                                (citation) => (
                                                    <p key={citation}>
                                                        Source: {citation}
                                                    </p>
                                                ),
                                            )}
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}

                        {processing && (
                            <>
                                {data.message.trim() && (
                                    <div className="flex justify-end">
                                        <div className="max-w-[85%] rounded-2xl rounded-br-sm bg-emerald-600 px-4 py-2.5 text-sm whitespace-pre-wrap text-white opacity-70 sm:max-w-[75%]">
                                            {data.message}
                                        </div>
                                    </div>
                                )}
                                <div className="flex justify-start">
                                    <div
                                        className="flex items-center gap-1 rounded-2xl rounded-bl-sm bg-slate-100 px-4 py-3"
                                        aria-label="Tutor is typing"
                                    >
                                        <span className="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:0ms]" />
                                        <span className="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:150ms]" />
                                        <span className="h-2 w-2 animate-bounce rounded-full bg-slate-400 [animation-delay:300ms]" />
                                    </div>
                                </div>
                            </>
                        )}

                        <div ref={bottomRef} />
                    </div>

                    {materialsReady ? (
                        <form onSubmit={submit} className="mt-3 flex gap-2">
                            <input
                                type="text"
                                value={data.message}
                                onChange={(event) =>
                                    setData('message', event.target.value)
                                }
                                placeholder="Type your question in any language; the tutor answers in English"
                                maxLength={5000}
                                className="min-w-0 flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 focus:outline-none"
                            />
                            <button
                                type="submit"
                                disabled={processing || !data.message.trim()}
                                className="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                            >
                                Send
                            </button>
                        </form>
                    ) : (
                        <p className="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            Your study materials aren&apos;t ready yet — please
                            check back soon or contact your teacher.
                        </p>
                    )}
                </div>
            </div>
        </GlcLayout>
    );
}
