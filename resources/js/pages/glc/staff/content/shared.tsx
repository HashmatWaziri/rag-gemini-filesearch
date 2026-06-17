import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardHeading,
    CardTitle,
    CardToolbar,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { router, useForm } from '@inertiajs/react';
import {
    BookOpen,
    Check,
    ChevronDown,
    FileUp,
    Headphones,
    Loader2,
    Mic,
    PenLine,
    Plus,
    SpellCheck,
    Trash2,
    type LucideIcon,
} from 'lucide-react';
import { useState, type ComponentProps, type ReactNode } from 'react';

export { xsrfToken } from '../ui';

/**
 * Shared design kit for the placement test content manager
 * (`/staff/placement-content`), adapted from Metronic Demo 7 account/settings
 * patterns. Every tab composes these primitives so the page reads as one
 * coherent surface regardless of which section is open.
 */

export interface ContentItemSettings {
    format?: 'mcq' | 'gap_fill';
    accepted_answers?: string[];
    [key: string]: unknown;
}

export interface ContentItem {
    id: number;
    section: string;
    type: string;
    parent_id: number | null;
    position: number;
    title: string | null;
    body: string | null;
    options: string[] | null;
    correct_option: number | null;
    settings: ContentItemSettings | null;
    audio_url: string | null;
    children: ContentItem[];
}

export interface ContentSections {
    reading: ContentItem[];
    grammar_vocabulary: ContentItem[];
    listening: ContentItem[];
    writing: ContentItem[];
    speaking: ContentItem[];
}

export type SectionKey = keyof ContentSections;
export type TabKey = SectionKey | 'pdf';

export const ITEMS_URL = '/staff/placement-content/items';
export const PDF_PREVIEW_URL = '/staff/placement-content/pdf-preview';

export const OPTION_LETTERS = ['A', 'B', 'C', 'D'] as const;

/** The five test sections in the fixed candidate-facing order. */
export const SECTION_KEYS: SectionKey[] = [
    'reading',
    'grammar_vocabulary',
    'listening',
    'writing',
    'speaking',
];

export interface SectionMeta {
    label: string;
    icon: LucideIcon;
    hint: string;
}

export const SECTION_META: Record<TabKey, SectionMeta> = {
    reading: {
        label: 'Reading',
        icon: BookOpen,
        hint: 'Passages with multiple-choice questions.',
    },
    grammar_vocabulary: {
        label: 'Grammar & Vocabulary',
        icon: SpellCheck,
        hint: 'Standalone multiple-choice questions.',
    },
    listening: {
        label: 'Listening',
        icon: Headphones,
        hint: 'Audio clips — candidates hear each clip once, no replay.',
    },
    writing: {
        label: 'Writing',
        icon: PenLine,
        hint: 'One prompt with word-count guidance.',
    },
    speaking: {
        label: 'Speaking',
        icon: Mic,
        hint: 'One prompt the candidate answers with a recording.',
    },
    pdf: {
        label: 'PDF Import',
        icon: FileUp,
        hint: 'Turn an uploaded PDF into a reading passage.',
    },
};

export function destroyItem(id: number): void {
    router.delete(`${ITEMS_URL}/${id}`, { preserveScroll: true });
}

/** Rounded primary-tinted icon frame used in section card headers. */
export function IconTile({
    icon: Icon,
    className,
}: {
    icon: LucideIcon;
    className?: string;
}) {
    return (
        <span
            aria-hidden
            className={cn(
                'flex size-10 shrink-0 items-center justify-center rounded-lg border border-primary/10 bg-primary/10 text-primary',
                className,
            )}
        >
            <Icon className="size-5" />
        </span>
    );
}

/** Metronic-style section card: icon tile + title/description header, toolbar right. */
export function SectionCard({
    icon,
    title,
    description,
    toolbar,
    children,
    className,
}: {
    icon: LucideIcon;
    title: ReactNode;
    description?: ReactNode;
    toolbar?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <Card className={className}>
            <CardHeader className="py-4">
                <CardHeading className="flex flex-row items-center gap-3.5 space-y-0">
                    <IconTile icon={icon} />
                    <div className="space-y-0.5">
                        <CardTitle className="text-mono">{title}</CardTitle>
                        {description && (
                            <CardDescription>{description}</CardDescription>
                        )}
                    </div>
                </CardHeading>
                {toolbar && <CardToolbar>{toolbar}</CardToolbar>}
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}

/** Centered empty-state onboarding card (Metronic members-starter pattern). */
export function EmptyStateCard({
    icon: Icon,
    title,
    description,
    children,
}: {
    icon: LucideIcon;
    title: string;
    description: string;
    children?: ReactNode;
}) {
    return (
        <Card>
            <CardContent className="flex flex-col items-center gap-3 px-6 py-12 text-center">
                <span
                    aria-hidden
                    className="flex size-14 items-center justify-center rounded-full bg-muted text-muted-foreground"
                >
                    <Icon className="size-6" />
                </span>
                <h3 className="text-base font-semibold text-mono">{title}</h3>
                <p className="max-w-sm text-sm text-secondary-foreground">
                    {description}
                </p>
                {children && <div className="mt-1">{children}</div>}
            </CardContent>
        </Card>
    );
}

/** Dashed add-record tile (Metronic "add role" tile pattern). */
export function AddTile({
    onClick,
    label,
    className,
}: {
    onClick: () => void;
    label: string;
    className?: string;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-border px-4 py-3 text-sm font-medium text-secondary-foreground transition-colors hover:border-primary/40 hover:text-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                className,
            )}
        >
            <Plus aria-hidden className="size-4" />
            {label}
        </button>
    );
}

export function FieldError({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return <p className="text-xs text-destructive">{message}</p>;
}

interface SaveButtonProps extends ComponentProps<typeof Button> {
    processing: boolean;
    recentlySuccessful?: boolean;
}

/** Primary action with processing spinner and a transient "Saved" state. */
export function SaveButton({
    processing,
    recentlySuccessful,
    children,
    disabled,
    ...props
}: SaveButtonProps) {
    return (
        <Button disabled={processing || disabled} {...props}>
            {processing ? (
                <Loader2 aria-hidden className="size-4 animate-spin" />
            ) : recentlySuccessful ? (
                <Check aria-hidden className="size-4" />
            ) : null}
            {recentlySuccessful ? 'Saved' : children}
        </Button>
    );
}

/** Destructive action guarded by a dialog — never use window.confirm. */
export function ConfirmDeleteDialog({
    trigger,
    title,
    description,
    confirmLabel = 'Delete',
    onConfirm,
}: {
    trigger: ReactNode;
    title: string;
    description: string;
    confirmLabel?: string;
    onConfirm: () => void;
}) {
    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>{trigger}</AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {description}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        className="bg-destructive text-white hover:bg-destructive/90"
                        onClick={onConfirm}
                    >
                        {confirmLabel}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

export type QuestionFormat = 'mcq' | 'true_false' | 'gap_fill';

export const QUESTION_FORMAT_LABELS: Record<QuestionFormat, string> = {
    mcq: 'Multiple choice',
    true_false: 'True / False',
    gap_fill: 'Gap fill',
};

const TRUE_FALSE_OPTIONS = ['True', 'False'];

/** Infers the authoring format of a question item from its stored data. */
export function questionFormat(item: ContentItem): QuestionFormat {
    if (item.settings?.format === 'gap_fill') {
        return 'gap_fill';
    }

    if (
        item.options?.length === 2 &&
        item.options[0] === TRUE_FALSE_OPTIONS[0] &&
        item.options[1] === TRUE_FALSE_OPTIONS[1]
    ) {
        return 'true_false';
    }

    return 'mcq';
}

/**
 * Question editor shared by Reading, Grammar & Vocabulary and Listening.
 * Listening questions can switch between multiple choice, true/false and gap
 * fill (IELTS-style formats); other sections stay multiple-choice only. The
 * correct answer is marked by clicking an option's letter — deliberately
 * unset for new questions so staff make an explicit choice.
 */
export function QuestionEditor({
    section,
    parentId,
    item,
    onSaved,
    onCancel,
}: {
    section: string;
    parentId: number | null;
    item?: ContentItem;
    onSaved?: () => void;
    onCancel?: () => void;
}) {
    const formatSelectable = section === 'listening';
    const [format, setFormat] = useState<QuestionFormat>(
        item ? questionFormat(item) : 'mcq',
    );

    const form = useForm<{
        section: string;
        type: string;
        parent_id: number | null;
        body: string;
        options: string[];
        correct_option: number | null;
        accepted_answers: string[];
        position: number | null;
    }>({
        section,
        type: 'question',
        parent_id: parentId,
        body: item?.body ?? '',
        options:
            item?.options && item.options.length === 4
                ? item.options
                : ['', '', '', ''],
        correct_option: item?.correct_option ?? null,
        accepted_answers: item?.settings?.accepted_answers ?? [''],
        position: item?.position ?? null,
    });

    const errors = form.errors as Record<string, string>;
    const fieldId = `question-${item?.id ?? 'new'}-${parentId ?? section}`;

    const switchFormat = (next: QuestionFormat) => {
        setFormat(next);

        if (next === 'true_false' && (form.data.correct_option ?? 0) > 1) {
            form.setData('correct_option', null);
        }
    };

    const submit = () => {
        form.transform((data) => {
            const base = {
                section: data.section,
                type: data.type,
                parent_id: data.parent_id,
                body: data.body,
                position: data.position,
            };

            if (format === 'gap_fill') {
                return {
                    ...base,
                    options: null,
                    correct_option: null,
                    settings: {
                        format: 'gap_fill',
                        accepted_answers: data.accepted_answers.filter(
                            (answer) => answer.trim() !== '',
                        ),
                    },
                };
            }

            return {
                ...base,
                options:
                    format === 'true_false' ? TRUE_FALSE_OPTIONS : data.options,
                correct_option: data.correct_option,
                settings: null,
            };
        });

        if (item) {
            form.put(`${ITEMS_URL}/${item.id}`, {
                preserveScroll: true,
                onSuccess: onSaved,
            });
        } else {
            form.post(ITEMS_URL, {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                    setFormat('mcq');
                    onSaved?.();
                },
            });
        }
    };

    const optionRows =
        format === 'true_false' ? TRUE_FALSE_OPTIONS : form.data.options;

    return (
        <div className="space-y-4">
            {formatSelectable && (
                <div className="space-y-1.5">
                    <span className="text-sm leading-none font-medium">
                        Question type
                    </span>
                    <div
                        role="group"
                        aria-label="Question type"
                        className="flex flex-wrap gap-1.5"
                    >
                        {(
                            Object.keys(
                                QUESTION_FORMAT_LABELS,
                            ) as QuestionFormat[]
                        ).map((key) => (
                            <button
                                key={key}
                                type="button"
                                aria-pressed={format === key}
                                onClick={() => switchFormat(key)}
                                className={cn(
                                    'rounded-md border px-2.5 py-1.5 text-xs font-medium transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                                    format === key
                                        ? 'border-primary/30 bg-primary/10 text-primary'
                                        : 'border-input bg-background text-secondary-foreground hover:bg-accent hover:text-foreground',
                                )}
                            >
                                {QUESTION_FORMAT_LABELS[key]}
                            </button>
                        ))}
                    </div>
                    <FieldError message={errors['settings.format']} />
                </div>
            )}

            <div className="space-y-1.5">
                <Label htmlFor={fieldId}>Question</Label>
                <Textarea
                    id={fieldId}
                    rows={2}
                    value={form.data.body}
                    placeholder={
                        format === 'gap_fill'
                            ? 'The train leaves from Platform _____.'
                            : 'Type the question exactly as the candidate will see it'
                    }
                    onChange={(e) => form.setData('body', e.target.value)}
                />
                {format === 'gap_fill' && (
                    <p className="text-xs text-muted-foreground">
                        Use _____ where the blank goes.
                    </p>
                )}
                <FieldError message={errors.body} />
            </div>

            {format === 'gap_fill' ? (
                <GapFillAnswersEditor
                    answers={form.data.accepted_answers}
                    onChange={(answers) =>
                        form.setData('accepted_answers', answers)
                    }
                    error={errors['settings.accepted_answers']}
                />
            ) : (
                <div className="space-y-1.5">
                    <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                        <span className="text-sm leading-none font-medium">
                            Options
                        </span>
                        <span
                            className={cn(
                                'text-xs',
                                form.data.correct_option === null
                                    ? 'text-amber-600'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {form.data.correct_option === null
                                ? 'Click a letter to mark the correct answer'
                                : `Correct answer: ${OPTION_LETTERS[form.data.correct_option]}`}
                        </span>
                    </div>
                    <div className="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                        {optionRows.map((option, index) => {
                            const correct = form.data.correct_option === index;

                            return (
                                <div
                                    key={index}
                                    className={cn(
                                        'flex items-center gap-2 rounded-lg border p-1.5 pe-2.5 transition-colors',
                                        correct
                                            ? 'border-green-500/40 bg-green-500/5'
                                            : 'border-input bg-background',
                                    )}
                                >
                                    <button
                                        type="button"
                                        aria-pressed={correct}
                                        aria-label={`Mark option ${OPTION_LETTERS[index]} as the correct answer`}
                                        title={
                                            correct
                                                ? 'Correct answer'
                                                : 'Mark as the correct answer'
                                        }
                                        onClick={() =>
                                            form.setData(
                                                'correct_option',
                                                index,
                                            )
                                        }
                                        className={cn(
                                            'flex size-7 shrink-0 items-center justify-center rounded-md text-xs font-semibold transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                                            correct
                                                ? 'bg-green-500 text-white'
                                                : 'bg-muted text-muted-foreground hover:bg-accent hover:text-foreground',
                                        )}
                                    >
                                        {correct ? (
                                            <Check className="size-4" />
                                        ) : (
                                            OPTION_LETTERS[index]
                                        )}
                                    </button>
                                    {format === 'true_false' ? (
                                        <span className="text-sm text-foreground">
                                            {option}
                                        </span>
                                    ) : (
                                        <input
                                            aria-label={`Option ${OPTION_LETTERS[index]}`}
                                            className="h-8 w-full bg-transparent text-sm placeholder:text-muted-foreground focus:outline-none"
                                            placeholder={`Option ${OPTION_LETTERS[index]}`}
                                            value={option}
                                            onChange={(e) => {
                                                const next = [
                                                    ...form.data.options,
                                                ];
                                                next[index] = e.target.value;
                                                form.setData('options', next);
                                            }}
                                        />
                                    )}
                                </div>
                            );
                        })}
                    </div>
                    <FieldError message={errors.options} />
                    <FieldError message={errors.correct_option} />
                </div>
            )}

            <div className="flex items-center gap-2.5">
                <SaveButton
                    size="sm"
                    processing={form.processing}
                    recentlySuccessful={form.recentlySuccessful}
                    onClick={submit}
                >
                    {item ? 'Save question' : 'Add question'}
                </SaveButton>
                {onCancel && (
                    <Button size="sm" variant="ghost" onClick={onCancel}>
                        Cancel
                    </Button>
                )}
                {item && (
                    <ConfirmDeleteDialog
                        title="Delete this question?"
                        description="The question is removed from the test for future candidates. This cannot be undone."
                        trigger={
                            <Button
                                size="sm"
                                variant="ghost"
                                className="ms-auto text-muted-foreground hover:text-destructive"
                            >
                                <Trash2 aria-hidden className="size-4" />
                                Delete
                            </Button>
                        }
                        onConfirm={() => destroyItem(item.id)}
                    />
                )}
            </div>
        </div>
    );
}

const MAX_ACCEPTED_ANSWERS = 10;

/** Gap-fill accepted answers list: 1–10 staff-only strings, add/remove rows. */
function GapFillAnswersEditor({
    answers,
    onChange,
    error,
}: {
    answers: string[];
    onChange: (answers: string[]) => void;
    error?: string;
}) {
    return (
        <div className="space-y-1.5">
            <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                <span className="text-sm leading-none font-medium">
                    Accepted answers
                </span>
                <span className="text-xs text-muted-foreground">
                    Staff-only — never shown to candidates
                </span>
            </div>
            <div className="space-y-2">
                {answers.map((answer, index) => (
                    <div key={index} className="flex items-center gap-2">
                        <input
                            aria-label={`Accepted answer ${index + 1}`}
                            className="h-8.5 w-full rounded-md border border-input bg-background px-3 text-sm placeholder:text-muted-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                            placeholder={`Accepted answer ${index + 1}`}
                            value={answer}
                            onChange={(e) => {
                                const next = [...answers];
                                next[index] = e.target.value;
                                onChange(next);
                            }}
                        />
                        <button
                            type="button"
                            disabled={answers.length <= 1}
                            onClick={() =>
                                onChange(answers.filter((_, i) => i !== index))
                            }
                            className="rounded-md p-1.5 text-muted-foreground transition-colors hover:text-destructive focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            <Trash2 aria-hidden className="size-4" />
                            <span className="sr-only">
                                Remove accepted answer {index + 1}
                            </span>
                        </button>
                    </div>
                ))}
            </div>
            {answers.length < MAX_ACCEPTED_ANSWERS && (
                <button
                    type="button"
                    onClick={() => onChange([...answers, ''])}
                    className="flex items-center gap-1.5 rounded-md px-1 py-0.5 text-xs font-medium text-secondary-foreground transition-colors hover:text-primary focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                >
                    <Plus aria-hidden className="size-3.5" />
                    Add accepted answer
                </button>
            )}
            <p className="text-xs text-muted-foreground">
                Answers are checked ignoring case and extra spaces.
            </p>
            <FieldError message={error} />
        </div>
    );
}

/** Collapsed question row: number chip, body excerpt, answer badge; expands to the editor. */
export function QuestionRow({
    question,
    index,
    section,
    parentId,
}: {
    question: ContentItem;
    index: number;
    section: string;
    parentId: number | null;
}) {
    const [open, setOpen] = useState(false);
    const format = questionFormat(question);
    const acceptedAnswers = question.settings?.accepted_answers ?? [];

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <CollapsibleTrigger className="group flex w-full items-center gap-3 px-4 py-3 text-start transition-colors hover:bg-accent/50 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none">
                <span
                    aria-hidden
                    className="flex h-6 min-w-6 shrink-0 items-center justify-center rounded-md bg-muted px-1 text-xs font-semibold text-muted-foreground"
                >
                    {index + 1}
                </span>
                <span className="min-w-0 grow truncate text-sm text-foreground">
                    {question.body || (
                        <span className="text-muted-foreground italic">
                            Untitled question
                        </span>
                    )}
                </span>
                {(format !== 'mcq' || question.section === 'listening') && (
                    <Badge
                        variant="outline"
                        className="text-2xs text-muted-foreground max-sm:hidden"
                    >
                        {QUESTION_FORMAT_LABELS[format]}
                    </Badge>
                )}
                {format === 'gap_fill'
                    ? acceptedAnswers.slice(0, 3).map((answer) => (
                          <Badge
                              key={answer}
                              variant="outline"
                              className="text-2xs text-secondary-foreground max-sm:hidden"
                          >
                              {answer}
                          </Badge>
                      ))
                    : question.correct_option !== null && (
                          <Badge
                              variant="outline"
                              className="text-2xs text-secondary-foreground max-sm:hidden"
                          >
                              Answer{' '}
                              {format === 'true_false'
                                  ? (question.options?.[
                                        question.correct_option
                                    ] ??
                                    OPTION_LETTERS[question.correct_option])
                                  : OPTION_LETTERS[question.correct_option]}
                          </Badge>
                      )}
                <ChevronDown
                    aria-hidden
                    className="size-4 shrink-0 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                />
                <span className="sr-only">
                    {open ? 'Collapse question' : 'Expand question to edit'}
                </span>
            </CollapsibleTrigger>
            <CollapsibleContent>
                <div className="border-t border-border bg-muted/30 p-4">
                    <QuestionEditor
                        section={section}
                        parentId={parentId}
                        item={question}
                        onSaved={() => setOpen(false)}
                        onCancel={() => setOpen(false)}
                    />
                </div>
            </CollapsibleContent>
        </Collapsible>
    );
}

/**
 * Question list + add flow for one container (passage, clip, or a whole
 * standalone section). The add editor stays open after a successful save so
 * staff can enter many questions in a row.
 */
export function QuestionsPanel({
    section,
    parentId,
    questions,
    heading = 'Questions',
}: {
    section: string;
    parentId: number | null;
    questions: ContentItem[];
    heading?: string;
}) {
    const [adding, setAdding] = useState(false);

    return (
        <div className="space-y-2.5">
            <div className="flex items-center gap-2">
                <h4 className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                    {heading}
                </h4>
                <Badge variant="outline" className="text-2xs">
                    {questions.length}
                </Badge>
            </div>

            {questions.length > 0 ? (
                <div className="divide-y divide-border overflow-hidden rounded-xl border border-border bg-card">
                    {questions.map((question, index) => (
                        <QuestionRow
                            key={question.id}
                            question={question}
                            index={index}
                            section={section}
                            parentId={parentId}
                        />
                    ))}
                </div>
            ) : (
                <p className="rounded-xl border border-dashed border-border px-4 py-3 text-sm text-muted-foreground">
                    No questions yet — add the first one below.
                </p>
            )}

            {adding ? (
                <div className="rounded-xl border border-border bg-card p-4">
                    <h5 className="mb-3 text-sm font-medium text-mono">
                        New question
                    </h5>
                    <QuestionEditor
                        section={section}
                        parentId={parentId}
                        onCancel={() => setAdding(false)}
                    />
                </div>
            ) : (
                <AddTile onClick={() => setAdding(true)} label="Add question" />
            )}
        </div>
    );
}
