import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { useForm } from '@inertiajs/react';
import {
    ChevronDown,
    CloudUpload,
    Headphones,
    Info,
    Trash2,
    X,
} from 'lucide-react';
import { useRef, useState } from 'react';
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

const AUDIO_ACCEPT = '.mp3,.wav,audio/mpeg,audio/wav';

function formatFileSize(bytes: number): string {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

/**
 * Listening tab: audio clips with their multiple-choice questions. Candidates
 * hear each clip exactly once during the test, so that rule is surfaced next
 * to every clip and in the upload flow.
 */
export function ListeningTab({ items }: { items: ContentItem[] }) {
    const clips = items.filter((i) => i.type === 'audio_clip');
    const [adding, setAdding] = useState(false);

    return (
        <div className="space-y-5">
            <div className="space-y-0.5">
                <h2 className="text-base font-semibold text-mono">
                    Listening clips
                </h2>
                <p className="text-sm text-muted-foreground">
                    {SECTION_META.listening.hint} Questions support IELTS-style
                    formats: multiple choice, true/false and gap fill.
                </p>
            </div>

            {clips.length === 0 && !adding && (
                <EmptyStateCard
                    icon={Headphones}
                    title="No listening clips yet"
                    description="Candidates will see an empty Listening section until you upload at least one clip with questions."
                >
                    <Button onClick={() => setAdding(true)}>
                        <CloudUpload aria-hidden className="size-4" />
                        Upload a clip
                    </Button>
                </EmptyStateCard>
            )}

            {clips.map((clip) => (
                <ClipCard
                    key={clip.id}
                    clip={clip}
                    defaultOpen={clips.length === 1}
                />
            ))}

            {adding ? (
                <NewClipCard onClose={() => setAdding(false)} />
            ) : (
                clips.length > 0 && (
                    <AddTile
                        onClick={() => setAdding(true)}
                        label="Add a listening clip"
                    />
                )
            )}
        </div>
    );
}

function ClipCard({
    clip,
    defaultOpen,
}: {
    clip: ContentItem;
    defaultOpen: boolean;
}) {
    const questionCount = clip.children.length;

    return (
        <Card>
            <Collapsible defaultOpen={defaultOpen} className="group/clip">
                {/* Hide the header's bottom border while collapsed so it does
                    not double up with the card's own bottom border. */}
                <CardHeader className="py-4 group-data-[state=closed]/clip:border-b-0">
                    <div className="min-w-0 space-y-0.5">
                        <p className="text-2xs font-semibold tracking-wide text-secondary-foreground uppercase">
                            Clip {clip.position}
                        </p>
                        <div className="flex flex-wrap items-center gap-2">
                            <h3 className="text-sm font-semibold text-mono">
                                {clip.title || (
                                    <span className="font-normal text-muted-foreground italic">
                                        Untitled clip
                                    </span>
                                )}
                            </h3>
                            <Badge variant="outline" className="text-2xs">
                                {questionCount} question
                                {questionCount === 1 ? '' : 's'}
                            </Badge>
                        </div>
                    </div>
                    <div className="flex items-center gap-1">
                        <ConfirmDeleteDialog
                            title="Delete this clip?"
                            description="The clip and its questions are removed from the test for future candidates. This cannot be undone."
                            trigger={
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    className="text-muted-foreground hover:text-destructive"
                                >
                                    <Trash2 aria-hidden className="size-4" />
                                    <span className="sr-only">Delete clip</span>
                                </Button>
                            }
                            onConfirm={() => destroyItem(clip.id)}
                        />
                        <CollapsibleTrigger asChild>
                            <Button
                                size="sm"
                                variant="ghost"
                                className="group text-muted-foreground"
                            >
                                <ChevronDown
                                    aria-hidden
                                    className="size-4 transition-transform group-data-[state=open]:rotate-180"
                                />
                                <span className="sr-only">
                                    Show or hide clip details
                                </span>
                            </Button>
                        </CollapsibleTrigger>
                    </div>
                </CardHeader>
                <CollapsibleContent>
                    <CardContent className="space-y-5">
                        {clip.audio_url && (
                            <div className="rounded-lg bg-muted/30 p-3">
                                <audio
                                    controls
                                    src={clip.audio_url}
                                    className="w-full"
                                />
                            </div>
                        )}

                        <p className="flex items-center gap-2 rounded-md bg-amber-500/10 px-3 py-2 text-xs text-amber-700 dark:text-amber-400">
                            <Info aria-hidden className="size-3.5 shrink-0" />
                            Candidates hear this clip once — it cannot be
                            replayed during the test.
                        </p>

                        <ClipEditForm clip={clip} />

                        <Separator />

                        <QuestionsPanel
                            section="listening"
                            parentId={clip.id}
                            questions={clip.children}
                        />
                    </CardContent>
                </CollapsibleContent>
            </Collapsible>
        </Card>
    );
}

function ClipEditForm({ clip }: { clip: ContentItem }) {
    const fileInputRef = useRef<HTMLInputElement>(null);

    // Method spoofing: the update goes out as POST so the file upload works,
    // while form.processing / recentlySuccessful keep driving SaveButton.
    const form = useForm<{
        _method: string;
        title: string;
        audio: File | null;
    }>({
        _method: 'put',
        title: clip.title ?? '',
        audio: null,
    });

    const clearFile = () => {
        form.setData('audio', null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const submit = () => {
        form.post(`${ITEMS_URL}/${clip.id}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: clearFile,
        });
    };

    const titleId = `clip-${clip.id}-title`;
    const audioId = `clip-${clip.id}-audio`;

    return (
        <div className="space-y-4">
            <div className="space-y-1.5">
                <Label htmlFor={titleId}>Title</Label>
                <Input
                    id={titleId}
                    value={form.data.title}
                    placeholder="A short name for this clip"
                    onChange={(e) => form.setData('title', e.target.value)}
                />
                <FieldError message={form.errors.title} />
            </div>

            <div className="space-y-1.5">
                <div className="flex flex-wrap items-center gap-2.5">
                    <label
                        htmlFor={audioId}
                        className="inline-flex h-8.5 cursor-pointer items-center gap-1.5 rounded-md border border-input bg-background px-3 text-2sm font-medium shadow-xs transition-colors hover:bg-accent has-[input:focus-visible]:ring-[3px] has-[input:focus-visible]:ring-ring/50"
                    >
                        <CloudUpload
                            aria-hidden
                            className="size-4 text-muted-foreground"
                        />
                        Replace audio
                        <input
                            ref={fileInputRef}
                            id={audioId}
                            type="file"
                            accept={AUDIO_ACCEPT}
                            className="sr-only"
                            onChange={(e) =>
                                form.setData(
                                    'audio',
                                    e.target.files?.[0] ?? null,
                                )
                            }
                        />
                    </label>
                    {form.data.audio && (
                        <span className="flex min-w-0 items-center gap-1.5 text-xs text-muted-foreground">
                            <span className="max-w-48 truncate">
                                {form.data.audio.name}
                            </span>
                            <span className="shrink-0">
                                ({formatFileSize(form.data.audio.size)})
                            </span>
                            <button
                                type="button"
                                onClick={clearFile}
                                className="rounded-sm p-0.5 transition-colors hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                            >
                                <X aria-hidden className="size-3.5" />
                                <span className="sr-only">
                                    Remove the chosen file
                                </span>
                            </button>
                        </span>
                    )}
                </div>
                <p className="text-xs text-muted-foreground">
                    MP3 or WAV, up to 20 MB. The current audio stays unless you
                    choose a new file.
                </p>
                <FieldError message={form.errors.audio} />
            </div>

            <SaveButton
                size="sm"
                processing={form.processing}
                recentlySuccessful={form.recentlySuccessful}
                onClick={submit}
            >
                Save clip
            </SaveButton>
        </div>
    );
}

function NewClipCard({ onClose }: { onClose: () => void }) {
    const form = useForm<{
        section: string;
        type: string;
        title: string;
        audio: File | null;
    }>({
        section: 'listening',
        type: 'audio_clip',
        title: '',
        audio: null,
    });

    const submit = () => {
        form.post(ITEMS_URL, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onClose();
            },
        });
    };

    const cancel = () => {
        form.reset();
        onClose();
    };

    return (
        <Card>
            <CardContent className="space-y-4 p-5">
                <h3 className="text-sm font-semibold text-mono">
                    New listening clip
                </h3>

                <div className="space-y-1.5">
                    <label
                        htmlFor="new-clip-audio"
                        className="flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-dashed border-border px-6 py-8 text-center transition-colors hover:border-primary/40 has-[input:focus-visible]:ring-[3px] has-[input:focus-visible]:ring-ring/50"
                    >
                        <span
                            aria-hidden
                            className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
                        >
                            <CloudUpload className="size-5" />
                        </span>
                        {form.data.audio ? (
                            <>
                                <span className="max-w-full truncate text-sm font-medium text-mono">
                                    {form.data.audio.name}
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    {formatFileSize(form.data.audio.size)} —
                                    click to choose a different file
                                </span>
                            </>
                        ) : (
                            <>
                                <span className="text-sm font-medium text-mono">
                                    Click to choose an MP3 or WAV file
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    Up to 20 MB — candidates hear it once
                                </span>
                            </>
                        )}
                        <input
                            id="new-clip-audio"
                            type="file"
                            accept={AUDIO_ACCEPT}
                            className="sr-only"
                            onChange={(e) =>
                                form.setData(
                                    'audio',
                                    e.target.files?.[0] ?? null,
                                )
                            }
                        />
                    </label>
                    <FieldError message={form.errors.audio} />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor="new-clip-title">Title</Label>
                    <Input
                        id="new-clip-title"
                        value={form.data.title}
                        placeholder="e.g. Ordering food at a cafe"
                        onChange={(e) => form.setData('title', e.target.value)}
                    />
                    <FieldError message={form.errors.title} />
                </div>

                <div className="flex items-center gap-2.5">
                    <SaveButton
                        size="sm"
                        processing={form.processing}
                        onClick={submit}
                    >
                        Upload clip
                    </SaveButton>
                    <Button size="sm" variant="ghost" onClick={cancel}>
                        Cancel
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
