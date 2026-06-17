import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { BookOpen, ChevronDown, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    AddTile,
    ConfirmDeleteDialog,
    destroyItem,
    EmptyStateCard,
    FieldError,
    ITEMS_URL,
    QuestionsPanel,
    SaveButton,
    SECTION_META,
    type ContentItem,
} from './shared';

const questionCount = (count: number): string =>
    `${count} question${count === 1 ? '' : 's'}`;

/** Edit form for an existing passage's title and text. */
function PassageEditForm({ passage }: { passage: ContentItem }) {
    const form = useForm<{
        section: string;
        type: string;
        title: string;
        body: string;
    }>({
        section: 'reading',
        type: 'passage',
        title: passage.title ?? '',
        body: passage.body ?? '',
    });

    return (
        <div className="space-y-4">
            <div className="space-y-1.5">
                <Label htmlFor={`passage-${passage.id}-title`}>Title</Label>
                <Input
                    id={`passage-${passage.id}-title`}
                    value={form.data.title}
                    placeholder="A short title staff use to recognise this passage"
                    onChange={(e) => form.setData('title', e.target.value)}
                />
                <FieldError message={form.errors.title} />
            </div>

            <div className="space-y-1.5">
                <Label htmlFor={`passage-${passage.id}-body`}>
                    Passage text
                </Label>
                <Textarea
                    id={`passage-${passage.id}-body`}
                    className="min-h-40"
                    value={form.data.body}
                    placeholder="The full text candidates read before answering the questions"
                    onChange={(e) => form.setData('body', e.target.value)}
                />
                <FieldError message={form.errors.body} />
            </div>

            <SaveButton
                size="sm"
                processing={form.processing}
                recentlySuccessful={form.recentlySuccessful}
                onClick={() =>
                    form.put(`${ITEMS_URL}/${passage.id}`, {
                        preserveScroll: true,
                    })
                }
            >
                Save passage
            </SaveButton>
        </div>
    );
}

/**
 * One collapsible card per passage: scannable header (number, title,
 * question count, body excerpt) with the edit form and its questions inside.
 */
function PassageCard({
    passage,
    defaultOpen,
}: {
    passage: ContentItem;
    defaultOpen: boolean;
}) {
    const [open, setOpen] = useState(defaultOpen);

    return (
        <Card>
            <Collapsible open={open} onOpenChange={setOpen}>
                <div className="flex items-start justify-between gap-2.5 px-5 py-4">
                    <div className="min-w-0 grow space-y-0.5">
                        <p className="text-2xs font-semibold tracking-wide text-secondary-foreground uppercase">
                            Passage {passage.position}
                        </p>
                        <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <h3 className="text-sm font-semibold text-mono">
                                {passage.title || (
                                    <span className="text-muted-foreground italic">
                                        Untitled passage
                                    </span>
                                )}
                            </h3>
                            <Badge
                                variant="outline"
                                className="text-2xs text-secondary-foreground"
                            >
                                {questionCount(passage.children.length)}
                            </Badge>
                        </div>
                        {!open && passage.body && (
                            <p className="truncate text-sm text-secondary-foreground">
                                {passage.body}
                            </p>
                        )}
                    </div>
                    <div className="flex shrink-0 items-center gap-1">
                        <ConfirmDeleteDialog
                            title="Delete this passage?"
                            description="The passage and its questions are removed from the test for future candidates. This cannot be undone."
                            trigger={
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    aria-label="Delete passage"
                                    className="text-muted-foreground hover:text-destructive"
                                >
                                    <Trash2 aria-hidden className="size-4" />
                                </Button>
                            }
                            onConfirm={() => destroyItem(passage.id)}
                        />
                        <CollapsibleTrigger asChild>
                            <Button
                                size="icon"
                                variant="ghost"
                                className="group text-muted-foreground"
                            >
                                <ChevronDown
                                    aria-hidden
                                    className="size-4 transition-transform group-data-[state=open]:rotate-180"
                                />
                                <span className="sr-only">
                                    {open
                                        ? 'Collapse passage'
                                        : 'Expand passage to edit'}
                                </span>
                            </Button>
                        </CollapsibleTrigger>
                    </div>
                </div>
                <CollapsibleContent>
                    <CardContent className="space-y-5 border-t border-border">
                        <PassageEditForm passage={passage} />
                        <div aria-hidden className="border-t border-border" />
                        <QuestionsPanel
                            section="reading"
                            parentId={passage.id}
                            questions={passage.children}
                        />
                    </CardContent>
                </CollapsibleContent>
            </Collapsible>
        </Card>
    );
}

function NewPassageForm({ onClose }: { onClose: () => void }) {
    const form = useForm<{
        section: string;
        type: string;
        title: string;
        body: string;
    }>({
        section: 'reading',
        type: 'passage',
        title: '',
        body: '',
    });

    const submit = () => {
        form.post(ITEMS_URL, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onClose();
            },
        });
    };

    return (
        <Card>
            <CardContent className="space-y-4">
                <h3 className="text-sm font-semibold text-mono">New passage</h3>

                <div className="space-y-1.5">
                    <Label htmlFor="new-passage-title">Title</Label>
                    <Input
                        id="new-passage-title"
                        value={form.data.title}
                        placeholder="A short title staff use to recognise this passage"
                        onChange={(e) => form.setData('title', e.target.value)}
                    />
                    <FieldError message={form.errors.title} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="new-passage-body">Passage text</Label>
                    <Textarea
                        id="new-passage-body"
                        className="min-h-40"
                        value={form.data.body}
                        placeholder="The full text candidates read before answering the questions"
                        onChange={(e) => form.setData('body', e.target.value)}
                    />
                    <FieldError message={form.errors.body} />
                </div>

                <div className="flex items-center gap-2.5">
                    <SaveButton
                        size="sm"
                        processing={form.processing}
                        recentlySuccessful={form.recentlySuccessful}
                        onClick={submit}
                    >
                        Create passage
                    </SaveButton>
                    <Button size="sm" variant="ghost" onClick={onClose}>
                        Cancel
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

export function ReadingTab({ items }: { items: ContentItem[] }) {
    const passages = items.filter((i) => i.type === 'passage');
    const [adding, setAdding] = useState(false);

    return (
        <div className="space-y-5">
            <div className="flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                <h2 className="text-base font-semibold text-mono">
                    Reading passages
                </h2>
                <p className="text-sm text-muted-foreground">
                    {SECTION_META.reading.hint}
                </p>
            </div>

            {passages.length === 0 && !adding && (
                <EmptyStateCard
                    icon={BookOpen}
                    title="No reading passages yet"
                    description="Candidates will see an empty Reading section until a passage is added. Start with a passage, then add its questions."
                >
                    <Button onClick={() => setAdding(true)}>
                        <Plus aria-hidden className="size-4" />
                        Add the first passage
                    </Button>
                </EmptyStateCard>
            )}

            {passages.map((passage) => (
                <PassageCard
                    key={passage.id}
                    passage={passage}
                    defaultOpen={passages.length === 1}
                />
            ))}

            {adding ? (
                <NewPassageForm onClose={() => setAdding(false)} />
            ) : (
                passages.length > 0 && (
                    <AddTile
                        onClick={() => setAdding(true)}
                        label="Add a new passage"
                    />
                )
            )}
        </div>
    );
}
