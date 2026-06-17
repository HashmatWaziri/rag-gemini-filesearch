import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { TriangleAlert } from 'lucide-react';
import type { FormEvent } from 'react';
import { CriteriaPanel, type CriteriaData } from './criteria-panel';
import {
    FieldError,
    ITEMS_URL,
    SaveButton,
    SECTION_META,
    SectionCard,
    type ContentItem,
} from './shared';

/**
 * Writing and Speaking each hold exactly one active prompt. Both tabs render
 * the same Metronic settings-card form; only the copy and the candidate-limit
 * fields differ.
 */

interface LimitField {
    key: string;
    label: string;
    caption: string;
    min?: number;
    max?: number;
}

const WRITING_LIMITS: LimitField[] = [
    {
        key: 'min_words',
        label: 'Minimum words',
        caption:
            'Essays below this are flagged to the candidate before submitting',
    },
    {
        key: 'max_words',
        label: 'Maximum words',
        caption: 'Soft limit — shown as guidance, not enforced',
    },
];

const SPEAKING_LIMITS: LimitField[] = [
    {
        key: 'max_duration_seconds',
        label: 'Max recording length',
        caption: 'Seconds the candidate can record per attempt',
        min: 10,
    },
    {
        key: 'max_attempts',
        label: 'Recording attempts',
        caption: 'How many tries the candidate gets, 1 to 5',
        min: 1,
        max: 5,
    },
];

function PromptCard({
    section,
    title,
    limits,
    prompt,
}: {
    section: 'writing' | 'speaking';
    title: string;
    limits: LimitField[];
    prompt: ContentItem | undefined;
}) {
    const meta = SECTION_META[section];

    const form = useForm<{
        section: string;
        type: string;
        title: string;
        body: string;
        settings: Record<string, number | string>;
    }>({
        section,
        type: 'prompt',
        title: prompt?.title ?? '',
        body: prompt?.body ?? '',
        settings: Object.fromEntries(
            limits.map((limit): [string, number | string] => [
                limit.key,
                (prompt?.settings?.[limit.key] as number | undefined) ?? '',
            ]),
        ),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (prompt) {
            form.put(`${ITEMS_URL}/${prompt.id}`, { preserveScroll: true });
        } else {
            form.post(ITEMS_URL, { preserveScroll: true });
        }
    };

    return (
        <SectionCard icon={meta.icon} title={title} description={meta.hint}>
            <form onSubmit={submit} className="space-y-5">
                {!prompt && (
                    <div className="flex items-start gap-2 rounded-lg bg-amber-500/10 px-3 py-2 text-sm text-amber-700 dark:text-amber-400">
                        <TriangleAlert
                            aria-hidden
                            className="mt-0.5 size-4 shrink-0"
                        />
                        <p>
                            Candidates currently see an empty {meta.label}{' '}
                            section — create the prompt below.
                        </p>
                    </div>
                )}

                <div className="space-y-1.5">
                    <Label htmlFor={`${section}-prompt-title`}>
                        Title (optional)
                    </Label>
                    <Input
                        id={`${section}-prompt-title`}
                        value={form.data.title}
                        onChange={(e) => form.setData('title', e.target.value)}
                    />
                </div>

                <div className="space-y-1.5">
                    <Label htmlFor={`${section}-prompt-body`}>
                        Prompt shown to the candidate
                    </Label>
                    <Textarea
                        id={`${section}-prompt-body`}
                        className="min-h-32"
                        value={form.data.body}
                        onChange={(e) => form.setData('body', e.target.value)}
                    />
                    <FieldError message={form.errors.body} />
                </div>

                <div className="space-y-1">
                    <h4 className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        Candidate limits
                    </h4>
                    <div>
                        {limits.map((limit) => (
                            <div
                                key={limit.key}
                                className="flex items-center justify-between gap-4 border-b border-border py-3 last:border-b-0"
                            >
                                <div className="min-w-0 space-y-0.5">
                                    <Label
                                        htmlFor={`${section}-${limit.key}`}
                                        className="text-sm font-medium"
                                    >
                                        {limit.label}
                                    </Label>
                                    <p className="text-xs text-muted-foreground">
                                        {limit.caption}
                                    </p>
                                </div>
                                <Input
                                    id={`${section}-${limit.key}`}
                                    type="number"
                                    min={limit.min}
                                    max={limit.max}
                                    className="w-28 shrink-0 text-end"
                                    value={form.data.settings[limit.key]}
                                    onChange={(e) =>
                                        form.setData('settings', {
                                            ...form.data.settings,
                                            [limit.key]:
                                                e.target.value === ''
                                                    ? ''
                                                    : Number(e.target.value),
                                        })
                                    }
                                />
                            </div>
                        ))}
                    </div>
                </div>

                <SaveButton
                    type="submit"
                    processing={form.processing}
                    recentlySuccessful={form.recentlySuccessful}
                >
                    {prompt ? 'Save prompt' : 'Create prompt'}
                </SaveButton>
            </form>
        </SectionCard>
    );
}

export function WritingTab({
    prompt,
    criteria,
}: {
    prompt: ContentItem | undefined;
    criteria: CriteriaData;
}) {
    return (
        <div className="space-y-5">
            <PromptCard
                section="writing"
                title="Writing prompt"
                limits={WRITING_LIMITS}
                prompt={prompt}
            />
            <CriteriaPanel skill="writing" data={criteria} />
        </div>
    );
}

export function SpeakingTab({
    prompt,
    criteria,
}: {
    prompt: ContentItem | undefined;
    criteria: CriteriaData;
}) {
    return (
        <div className="space-y-5">
            <PromptCard
                section="speaking"
                title="Speaking prompt"
                limits={SPEAKING_LIMITS}
                prompt={prompt}
            />
            <CriteriaPanel skill="speaking" data={criteria} />
        </div>
    );
}
