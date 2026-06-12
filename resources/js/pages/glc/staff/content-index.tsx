import { GlcSettingsSidebarLayout } from '@/components/glc';
import GlcLayout from '@/layouts/glc-layout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';
import {
    Badge,
    btnDanger,
    btnPrimary,
    btnSecondary,
    Card,
    Field,
    inputCls,
    SECTION_LABELS,
    xsrfToken,
} from './ui';

interface ContentItem {
    id: number;
    section: string;
    type: string;
    parent_id: number | null;
    position: number;
    title: string | null;
    body: string | null;
    options: string[] | null;
    correct_option: number | null;
    settings: Record<string, number> | null;
    audio_url: string | null;
    children: ContentItem[];
}

interface PageProps {
    sections: Record<string, ContentItem[]>;
    errors: Record<string, string>;
    [key: string]: unknown;
}

const ITEMS_URL = '/staff/placement-content/items';

const TABS = [
    { key: 'reading', label: 'Reading' },
    { key: 'grammar_vocabulary', label: 'Grammar & Vocab' },
    { key: 'listening', label: 'Listening' },
    { key: 'writing', label: 'Writing' },
    { key: 'speaking', label: 'Speaking' },
    { key: 'pdf', label: 'PDF Import' },
] as const;

function QuestionForm({
    section,
    parentId,
    item,
    onDone,
}: {
    section: string;
    parentId: number | null;
    item?: ContentItem;
    onDone?: () => void;
}) {
    const form = useForm({
        section,
        type: 'question',
        parent_id: parentId,
        body: item?.body ?? '',
        options: item?.options ?? ['', '', '', ''],
        correct_option: item?.correct_option ?? 0,
        position: item?.position ?? null,
    });

    const submit = () => {
        if (item) {
            form.put(`${ITEMS_URL}/${item.id}`, {
                preserveScroll: true,
                onSuccess: onDone,
            });
        } else {
            form.post(ITEMS_URL, {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                    onDone?.();
                },
            });
        }
    };

    return (
        <div className="space-y-2 rounded-md border border-border bg-muted/50 p-3">
            <Field label="Question" error={form.errors.body}>
                <input
                    className={inputCls}
                    value={form.data.body}
                    onChange={(e) => form.setData('body', e.target.value)}
                />
            </Field>
            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <RadioGroup
                    value={String(form.data.correct_option)}
                    onValueChange={(value) =>
                        form.setData('correct_option', Number(value))
                    }
                    className="contents"
                >
                    {form.data.options.map((option, index) => (
                        <div key={index} className="flex items-center gap-2">
                            <RadioGroupItem
                                value={String(index)}
                                id={`correct-${item?.id ?? 'new'}-${parentId ?? section}-${index}`}
                            />
                            <input
                                className={inputCls}
                                placeholder={`Option ${index + 1}`}
                                value={option}
                                onChange={(e) => {
                                    const next = [...form.data.options];
                                    next[index] = e.target.value;
                                    form.setData('options', next);
                                }}
                            />
                        </div>
                    ))}
                </RadioGroup>
            </div>
            {(form.errors as Record<string, string>)['options'] && (
                <p className="text-xs text-red-600">
                    {(form.errors as Record<string, string>)['options']}
                </p>
            )}
            <div className="flex items-center gap-2">
                <button
                    type="button"
                    className={btnPrimary}
                    disabled={form.processing}
                    onClick={submit}
                >
                    {item ? 'Update question' : 'Add question'}
                </button>
                {item && (
                    <button
                        type="button"
                        className={btnDanger}
                        onClick={() => {
                            if (confirm('Delete this question?')) {
                                router.delete(`${ITEMS_URL}/${item.id}`, {
                                    preserveScroll: true,
                                });
                            }
                        }}
                    >
                        Delete
                    </button>
                )}
            </div>
        </div>
    );
}

function PassageEditor({ passage }: { passage: ContentItem }) {
    const form = useForm({
        title: passage.title ?? '',
        body: passage.body ?? '',
        position: passage.position,
    });
    const [showNewQuestion, setShowNewQuestion] = useState(false);

    return (
        <Card
            title={`Passage ${passage.position}: ${passage.title ?? ''}`}
            aside={
                <button
                    type="button"
                    className={btnDanger}
                    onClick={() => {
                        if (
                            confirm(
                                'Delete this passage and all its questions?',
                            )
                        ) {
                            router.delete(`${ITEMS_URL}/${passage.id}`, {
                                preserveScroll: true,
                            });
                        }
                    }}
                >
                    Delete passage
                </button>
            }
        >
            <div className="space-y-2">
                <Field label="Title" error={form.errors.title}>
                    <input
                        className={inputCls}
                        value={form.data.title}
                        onChange={(e) => form.setData('title', e.target.value)}
                    />
                </Field>
                <Field label="Passage text" error={form.errors.body}>
                    <textarea
                        className={`${inputCls} min-h-32`}
                        value={form.data.body}
                        onChange={(e) => form.setData('body', e.target.value)}
                    />
                </Field>
                <button
                    type="button"
                    className={btnSecondary}
                    disabled={form.processing}
                    onClick={() =>
                        form.put(`${ITEMS_URL}/${passage.id}`, {
                            preserveScroll: true,
                        })
                    }
                >
                    Save passage
                </button>
            </div>

            <h3 className="mt-4 mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                Questions ({passage.children.length})
            </h3>
            <div className="space-y-3">
                {passage.children.map((question) => (
                    <QuestionForm
                        key={question.id}
                        section={passage.section}
                        parentId={passage.id}
                        item={question}
                    />
                ))}
                {showNewQuestion ? (
                    <QuestionForm
                        section={passage.section}
                        parentId={passage.id}
                        onDone={() => setShowNewQuestion(false)}
                    />
                ) : (
                    <button
                        type="button"
                        className={btnSecondary}
                        onClick={() => setShowNewQuestion(true)}
                    >
                        Add question
                    </button>
                )}
            </div>
        </Card>
    );
}

function NewPassageForm() {
    const form = useForm({
        section: 'reading',
        type: 'passage',
        title: '',
        body: '',
    });

    return (
        <Card title="Add a new passage">
            <div className="space-y-2">
                <Field label="Title" error={form.errors.title}>
                    <input
                        className={inputCls}
                        value={form.data.title}
                        onChange={(e) => form.setData('title', e.target.value)}
                    />
                </Field>
                <Field label="Passage text" error={form.errors.body}>
                    <textarea
                        className={`${inputCls} min-h-32`}
                        value={form.data.body}
                        onChange={(e) => form.setData('body', e.target.value)}
                    />
                </Field>
                <button
                    type="button"
                    className={btnPrimary}
                    disabled={form.processing}
                    onClick={() =>
                        form.post(ITEMS_URL, {
                            preserveScroll: true,
                            onSuccess: () => form.reset(),
                        })
                    }
                >
                    Create passage
                </button>
            </div>
        </Card>
    );
}

function ClipEditor({ clip }: { clip: ContentItem }) {
    const form = useForm<{
        title: string;
        audio: File | null;
    }>({ title: clip.title ?? '', audio: null });
    const [showNewQuestion, setShowNewQuestion] = useState(false);

    return (
        <Card
            title={`Clip ${clip.position}: ${clip.title ?? ''}`}
            aside={
                <button
                    type="button"
                    className={btnDanger}
                    onClick={() => {
                        if (confirm('Delete this clip and its questions?')) {
                            router.delete(`${ITEMS_URL}/${clip.id}`, {
                                preserveScroll: true,
                            });
                        }
                    }}
                >
                    Delete clip
                </button>
            }
        >
            <div className="space-y-2">
                {clip.audio_url && (
                    <audio controls src={clip.audio_url} className="w-full" />
                )}
                <Field label="Title" error={form.errors.title}>
                    <input
                        className={inputCls}
                        value={form.data.title}
                        onChange={(e) => form.setData('title', e.target.value)}
                    />
                </Field>
                <Field
                    label="Replace audio (MP3/WAV)"
                    error={form.errors.audio}
                >
                    <input
                        type="file"
                        accept=".mp3,.wav,audio/mpeg,audio/wav"
                        className="text-sm"
                        onChange={(e) =>
                            form.setData('audio', e.target.files?.[0] ?? null)
                        }
                    />
                </Field>
                <button
                    type="button"
                    className={btnSecondary}
                    disabled={form.processing}
                    onClick={() =>
                        router.post(
                            `${ITEMS_URL}/${clip.id}`,
                            {
                                _method: 'put',
                                title: form.data.title,
                                audio: form.data.audio,
                            },
                            { preserveScroll: true, forceFormData: true },
                        )
                    }
                >
                    Save clip
                </button>
            </div>

            <h3 className="mt-4 mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                Questions ({clip.children.length})
            </h3>
            <div className="space-y-3">
                {clip.children.map((question) => (
                    <QuestionForm
                        key={question.id}
                        section="listening"
                        parentId={clip.id}
                        item={question}
                    />
                ))}
                {showNewQuestion ? (
                    <QuestionForm
                        section="listening"
                        parentId={clip.id}
                        onDone={() => setShowNewQuestion(false)}
                    />
                ) : (
                    <button
                        type="button"
                        className={btnSecondary}
                        onClick={() => setShowNewQuestion(true)}
                    >
                        Add question
                    </button>
                )}
            </div>
        </Card>
    );
}

function NewClipForm() {
    const form = useForm<{
        section: string;
        type: string;
        title: string;
        audio: File | null;
    }>({ section: 'listening', type: 'audio_clip', title: '', audio: null });

    return (
        <Card title="Add a new listening clip">
            <div className="space-y-2">
                <Field label="Title" error={form.errors.title}>
                    <input
                        className={inputCls}
                        value={form.data.title}
                        onChange={(e) => form.setData('title', e.target.value)}
                    />
                </Field>
                <Field label="Audio file (MP3/WAV)" error={form.errors.audio}>
                    <input
                        type="file"
                        accept=".mp3,.wav,audio/mpeg,audio/wav"
                        className="text-sm"
                        onChange={(e) =>
                            form.setData('audio', e.target.files?.[0] ?? null)
                        }
                    />
                </Field>
                <button
                    type="button"
                    className={btnPrimary}
                    disabled={form.processing}
                    onClick={() =>
                        form.post(ITEMS_URL, {
                            preserveScroll: true,
                            forceFormData: true,
                            onSuccess: () => form.reset(),
                        })
                    }
                >
                    Upload clip
                </button>
            </div>
        </Card>
    );
}

function PromptEditor({
    section,
    prompt,
    settingsFields,
}: {
    section: 'writing' | 'speaking';
    prompt: ContentItem | undefined;
    settingsFields: { key: string; label: string }[];
}) {
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
            settingsFields.map(({ key }) => [
                key,
                prompt?.settings?.[key] ?? '',
            ]),
        ),
    });

    const settings = form.data.settings;

    return (
        <Card title={`${SECTION_LABELS[section]} prompt`}>
            <div className="space-y-2">
                <Field label="Title">
                    <input
                        className={inputCls}
                        value={form.data.title}
                        onChange={(e) => form.setData('title', e.target.value)}
                    />
                </Field>
                <Field label="Prompt" error={form.errors.body}>
                    <textarea
                        className={`${inputCls} min-h-28`}
                        value={form.data.body}
                        onChange={(e) => form.setData('body', e.target.value)}
                    />
                </Field>
                <div className="grid grid-cols-2 gap-2">
                    {settingsFields.map(({ key, label }) => (
                        <Field key={key} label={label}>
                            <input
                                type="number"
                                className={inputCls}
                                value={settings[key]}
                                onChange={(e) =>
                                    form.setData('settings', {
                                        ...settings,
                                        [key]: e.target.value
                                            ? Number(e.target.value)
                                            : '',
                                    })
                                }
                            />
                        </Field>
                    ))}
                </div>
                <button
                    type="button"
                    className={btnPrimary}
                    disabled={form.processing}
                    onClick={() =>
                        prompt
                            ? form.put(`${ITEMS_URL}/${prompt.id}`, {
                                  preserveScroll: true,
                              })
                            : form.post(ITEMS_URL, { preserveScroll: true })
                    }
                >
                    {prompt ? 'Save prompt' : 'Create prompt'}
                </button>
            </div>
        </Card>
    );
}

function PdfImportPanel() {
    const [file, setFile] = useState<File | null>(null);
    const [extracting, setExtracting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const createForm = useForm({
        section: 'reading',
        type: 'passage',
        title: '',
        body: '',
    });

    const extract = async () => {
        if (!file) return;
        setExtracting(true);
        setError(null);

        try {
            const formData = new FormData();
            formData.append('pdf', file);

            const response = await fetch(
                '/staff/placement-content/pdf-preview',
                {
                    method: 'POST',
                    headers: { 'X-XSRF-TOKEN': xsrfToken() },
                    body: formData,
                },
            );
            const json = await response.json();

            if (!response.ok) {
                setError(json.message ?? 'The PDF could not be parsed.');
            } else {
                setPreview(json.text);
                createForm.setData('body', json.text);
            }
        } catch {
            setError('The PDF could not be uploaded.');
        } finally {
            setExtracting(false);
        }
    };

    return (
        <Card title="Import placement content from PDF">
            <p className="mb-3 text-xs text-muted-foreground">
                Upload a PDF, review the extracted text below, then confirm to
                create a reading passage from it. Nothing becomes active before
                you confirm.
            </p>
            <div className="flex flex-wrap items-center gap-2">
                <input
                    type="file"
                    accept=".pdf,application/pdf"
                    className="text-sm"
                    onChange={(e) => setFile(e.target.files?.[0] ?? null)}
                />
                <button
                    type="button"
                    className={btnSecondary}
                    disabled={!file || extracting}
                    onClick={() => void extract()}
                >
                    {extracting ? 'Extracting…' : 'Extract text preview'}
                </button>
            </div>
            {error && <p className="mt-2 text-sm text-red-600">{error}</p>}

            {preview !== null && (
                <div className="mt-4 space-y-2 border-t border-border pt-3">
                    <Badge tone="amber">
                        Preview — review before creating content
                    </Badge>
                    <Field
                        label="Passage title"
                        error={createForm.errors.title}
                    >
                        <input
                            className={inputCls}
                            value={createForm.data.title}
                            onChange={(e) =>
                                createForm.setData('title', e.target.value)
                            }
                        />
                    </Field>
                    <Field
                        label="Extracted text (editable)"
                        error={createForm.errors.body}
                    >
                        <textarea
                            className={`${inputCls} min-h-48`}
                            value={createForm.data.body}
                            onChange={(e) =>
                                createForm.setData('body', e.target.value)
                            }
                        />
                    </Field>
                    <button
                        type="button"
                        className={btnPrimary}
                        disabled={createForm.processing}
                        onClick={() =>
                            createForm.post(ITEMS_URL, {
                                preserveScroll: true,
                                onSuccess: () => {
                                    setPreview(null);
                                    setFile(null);
                                    createForm.reset();
                                },
                            })
                        }
                    >
                        Create passage from this text
                    </button>
                </div>
            )}
        </Card>
    );
}

export default function ContentIndex() {
    const { sections } = usePage<PageProps>().props;
    const [tab, setTab] = useState<string>('reading');

    const writingPrompt = sections.writing.find((i) => i.type === 'prompt');
    const speakingPrompt = sections.speaking.find((i) => i.type === 'prompt');

    return (
        <GlcLayout title="Placement Test Content">
            <Head title="Placement Test Content" />

            <p className="mb-4 text-sm text-muted-foreground">
                One fixed form, same items and order for every candidate. Only
                active items appear in the test.
            </p>

            <GlcSettingsSidebarLayout
                items={TABS.map(({ key, label }) => ({
                    id: key,
                    label,
                    active: tab === key,
                }))}
                onSelect={(id) => setTab(id)}
            >
            <div className="space-y-5 lg:space-y-7.5">
                {tab === 'reading' && (
                    <>
                        {sections.reading
                            .filter((item) => item.type === 'passage')
                            .map((passage) => (
                                <PassageEditor
                                    key={passage.id}
                                    passage={passage}
                                />
                            ))}
                        <NewPassageForm />
                    </>
                )}

                {tab === 'grammar_vocabulary' && (
                    <Card
                        title={`Standalone questions (${sections.grammar_vocabulary.length})`}
                    >
                        <div className="space-y-3">
                            {sections.grammar_vocabulary.map((question) => (
                                <QuestionForm
                                    key={question.id}
                                    section="grammar_vocabulary"
                                    parentId={null}
                                    item={question}
                                />
                            ))}
                            <h3 className="pt-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                Add question
                            </h3>
                            <QuestionForm
                                section="grammar_vocabulary"
                                parentId={null}
                            />
                        </div>
                    </Card>
                )}

                {tab === 'listening' && (
                    <>
                        {sections.listening
                            .filter((item) => item.type === 'audio_clip')
                            .map((clip) => (
                                <ClipEditor key={clip.id} clip={clip} />
                            ))}
                        <NewClipForm />
                    </>
                )}

                {tab === 'writing' && (
                    <PromptEditor
                        section="writing"
                        prompt={writingPrompt}
                        settingsFields={[
                            { key: 'min_words', label: 'Minimum words' },
                            { key: 'max_words', label: 'Maximum words (soft)' },
                        ]}
                    />
                )}

                {tab === 'speaking' && (
                    <PromptEditor
                        section="speaking"
                        prompt={speakingPrompt}
                        settingsFields={[
                            {
                                key: 'max_duration_seconds',
                                label: 'Max duration (seconds)',
                            },
                            { key: 'max_attempts', label: 'Max attempts' },
                        ]}
                    />
                )}

                {tab === 'pdf' && <PdfImportPanel />}
            </div>
            </GlcSettingsSidebarLayout>
        </GlcLayout>
    );
}
