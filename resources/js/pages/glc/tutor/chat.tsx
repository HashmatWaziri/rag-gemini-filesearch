import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { MenuIcon } from 'lucide-react';
import { useEffect, useRef, type FormEvent } from 'react';

interface Assignment {
    course: string;
    level: string;
    unit: string;
}

interface CurriculumSource {
    document_id: number;
    version: number;
    title: string;
}

interface Message {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    citations: string[];
    curriculum_sources: CurriculumSource[];
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

function formatCitationLabel(
    citation: string,
    curriculumSources: CurriculumSource[],
): string {
    const source = curriculumSources.find((item) =>
        citation.startsWith(`${item.title} (`),
    );

    if (!source || source.version <= 1) {
        return citation;
    }

    return citation.replace(source.title, `${source.title} (v${source.version})`);
}

function ConversationList({
    conversations,
    activeId,
}: {
    conversations: ConversationItem[];
    activeId: number;
}) {
    return (
        <ul className="space-y-1">
            {conversations.map((item) => (
                <li key={item.id}>
                    <Link
                        href={`/tutor/conversations/${item.id}`}
                        className={`block truncate rounded-md px-3 py-2 text-sm ${
                            item.id === activeId
                                ? 'bg-primary/10 font-medium text-primary'
                                : 'text-secondary-foreground hover:bg-accent'
                        }`}
                    >
                        {item.title ?? 'New conversation'}
                    </Link>
                </li>
            ))}
        </ul>
    );
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
    const pageTitle = conversation.title ?? 'AI Tutor';

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
        <GlcLayout title={pageTitle}>
            <Head title={pageTitle} />

            <div className="flex gap-6">
                <aside className="hidden w-56 shrink-0 md:block">
                    <Button
                        type="button"
                        className="mb-3 w-full"
                        onClick={() => router.post('/tutor/conversations')}
                    >
                        New conversation
                    </Button>
                    <ConversationList
                        conversations={conversations}
                        activeId={conversation.id}
                    />
                </aside>

                <div className="flex min-h-[70vh] flex-1 flex-col">
                    <div className="mb-3 flex items-center justify-between gap-2">
                        <div className="flex items-center gap-2 md:hidden">
                            <Sheet>
                                <SheetTrigger asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="icon"
                                        aria-label="Open conversations"
                                    >
                                        <MenuIcon className="size-4" />
                                    </Button>
                                </SheetTrigger>
                                <SheetContent side="left" className="w-72">
                                    <SheetHeader>
                                        <SheetTitle>Conversations</SheetTitle>
                                    </SheetHeader>
                                    <Button
                                        type="button"
                                        className="mt-4 w-full"
                                        onClick={() =>
                                            router.post('/tutor/conversations')
                                        }
                                    >
                                        New conversation
                                    </Button>
                                    <div className="mt-4">
                                        <ConversationList
                                            conversations={conversations}
                                            activeId={conversation.id}
                                        />
                                    </div>
                                </SheetContent>
                            </Sheet>
                            <Link
                                href="/tutor"
                                className="text-sm font-medium text-primary hover:underline"
                            >
                                Back
                            </Link>
                        </div>
                        <p className="truncate text-xs text-muted-foreground">
                            {assignment.course} / {assignment.level} /{' '}
                            {assignment.unit}
                        </p>
                    </div>

                    <p className="mb-3 rounded-md bg-muted px-3 py-2 text-xs text-secondary-foreground">
                        The tutor replies in English only. It guides your
                        homework with hints and explanations, and does not give
                        direct answers.
                    </p>

                    <ScrollArea className="flex-1 rounded-lg border border-border bg-card">
                        <div className="space-y-3 p-4">
                            {messages.length === 0 && !processing && (
                                <p className="py-8 text-center text-sm text-muted-foreground">
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
                                                ? 'rounded-br-sm bg-primary text-primary-foreground'
                                                : 'rounded-bl-sm bg-muted text-foreground'
                                        }`}
                                    >
                                        {message.content}
                                        {message.citations.length > 0 && (
                                            <div className="mt-2 space-y-0.5 border-t border-border pt-1.5 text-xs text-muted-foreground">
                                                {message.citations.map(
                                                    (citation) => (
                                                        <p key={citation}>
                                                            Source:{' '}
                                                            {formatCitationLabel(
                                                                citation,
                                                                message.curriculum_sources,
                                                            )}
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
                                            <div className="max-w-[85%] rounded-2xl rounded-br-sm bg-primary px-4 py-2.5 text-sm whitespace-pre-wrap text-primary-foreground opacity-70 sm:max-w-[75%]">
                                                {data.message}
                                            </div>
                                        </div>
                                    )}
                                    <div className="flex justify-start">
                                        <div
                                            className="flex items-center gap-1 rounded-2xl rounded-bl-sm bg-muted px-4 py-3"
                                            aria-label="Tutor is typing"
                                        >
                                            <span className="h-2 w-2 animate-bounce rounded-full bg-muted-foreground [animation-delay:0ms]" />
                                            <span className="h-2 w-2 animate-bounce rounded-full bg-muted-foreground [animation-delay:150ms]" />
                                            <span className="h-2 w-2 animate-bounce rounded-full bg-muted-foreground [animation-delay:300ms]" />
                                        </div>
                                    </div>
                                </>
                            )}

                            <div ref={bottomRef} />
                        </div>
                    </ScrollArea>

                    {materialsReady ? (
                        <form
                            onSubmit={submit}
                            className="mt-3 flex items-end gap-2"
                        >
                            <Textarea
                                value={data.message}
                                onChange={(event) =>
                                    setData('message', event.target.value)
                                }
                                placeholder="Type your question in any language; the tutor answers in English"
                                maxLength={5000}
                                rows={2}
                                className="min-h-0 min-w-0 flex-1 resize-none"
                                onKeyDown={(event) => {
                                    if (
                                        event.key === 'Enter' &&
                                        !event.shiftKey
                                    ) {
                                        event.preventDefault();
                                        submit(event);
                                    }
                                }}
                            />
                            <Button
                                type="submit"
                                disabled={processing || !data.message.trim()}
                            >
                                Send
                            </Button>
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
