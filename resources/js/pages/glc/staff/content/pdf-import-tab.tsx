import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/react';
import { Check, CloudUpload, Loader2 } from 'lucide-react';
import { useState, type ChangeEvent, type FormEvent } from 'react';
import {
    FieldError,
    ITEMS_URL,
    PDF_PREVIEW_URL,
    SaveButton,
    SECTION_META,
    SectionCard,
    xsrfToken,
} from './shared';

const STEPS = ['Upload', 'Review', 'Create passage'] as const;

function formatFileSize(bytes: number): string {
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
}

/** Compact 3-step indicator echoing the page's placement-process stepper. */
function StepIndicator({ current }: { current: number }) {
    return (
        <ol
            aria-label="Import steps"
            className="flex flex-wrap items-center gap-x-2.5 gap-y-2"
        >
            {STEPS.map((label, index) => {
                const step = index + 1;
                const state =
                    step < current
                        ? 'done'
                        : step === current
                          ? 'current'
                          : 'upcoming';

                return (
                    <li key={label} className="flex items-center gap-2.5">
                        {index > 0 && (
                            <span
                                aria-hidden
                                className="h-px w-4 border-t border-dashed border-zinc-300 sm:w-6 dark:border-zinc-600"
                            />
                        )}
                        <span className="flex items-center gap-1.5">
                            <span
                                aria-hidden
                                className={cn(
                                    'flex size-6 shrink-0 items-center justify-center rounded-full text-2xs font-semibold',
                                    state === 'current' &&
                                        'bg-primary/10 text-primary',
                                    state === 'done' &&
                                        'bg-green-500/10 text-green-500',
                                    state === 'upcoming' &&
                                        'bg-muted text-muted-foreground',
                                )}
                            >
                                {state === 'done' ? (
                                    <Check className="size-3.5" />
                                ) : (
                                    step
                                )}
                            </span>
                            <span
                                className={cn(
                                    'text-xs font-medium',
                                    state === 'current'
                                        ? 'text-primary'
                                        : state === 'done'
                                          ? 'text-secondary-foreground'
                                          : 'text-muted-foreground',
                                )}
                            >
                                {label}
                                <span className="sr-only">
                                    {state === 'done'
                                        ? ' (done)'
                                        : state === 'current'
                                          ? ' (current step)'
                                          : ''}
                                </span>
                            </span>
                        </span>
                    </li>
                );
            })}
        </ol>
    );
}

export function PdfImportTab() {
    const [file, setFile] = useState<File | null>(null);
    const [extracting, setExtracting] = useState(false);
    const [preview, setPreview] = useState<string | null>(null);
    const [extractError, setExtractError] = useState<string | null>(null);
    const [createdHint, setCreatedHint] = useState(false);

    const createForm = useForm({
        section: 'reading',
        type: 'passage',
        title: '',
        body: '',
    });

    const currentStep = extracting || preview !== null ? 2 : 1;

    const chooseFile = (event: ChangeEvent<HTMLInputElement>) => {
        const chosen = event.target.files?.[0] ?? null;

        // Clear the input so picking the same file again still fires onChange.
        event.target.value = '';

        if (!chosen) {
            return;
        }

        setFile(chosen);
        setPreview(null);
        setExtractError(null);
        setCreatedHint(false);
        createForm.reset();
        createForm.clearErrors();
    };

    const extractPreview = async () => {
        if (!file) {
            return;
        }

        setExtracting(true);
        setExtractError(null);

        try {
            const formData = new FormData();
            formData.append('pdf', file);

            const response = await fetch(PDF_PREVIEW_URL, {
                method: 'POST',
                headers: { 'X-XSRF-TOKEN': xsrfToken() },
                body: formData,
            });

            const payload: { text?: string; message?: string } =
                await response.json();

            if (response.ok && typeof payload.text === 'string') {
                setPreview(payload.text);
                createForm.setData('body', payload.text);
            } else {
                setExtractError(
                    payload.message ?? 'The PDF could not be uploaded.',
                );
            }
        } catch {
            setExtractError('The PDF could not be uploaded.');
        } finally {
            setExtracting(false);
        }
    };

    const createPassage = (event: FormEvent) => {
        event.preventDefault();

        createForm.post(ITEMS_URL, {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                setPreview(null);
                setFile(null);
                setCreatedHint(true);
            },
        });
    };

    return (
        <div className="space-y-5">
            <SectionCard
                icon={SECTION_META.pdf.icon}
                title="Import from PDF"
                description="Upload a PDF, review the extracted text, then turn it into a Reading passage. Nothing goes live until you confirm."
            >
                <div className="space-y-5">
                    <StepIndicator current={currentStep} />

                    {createdHint && (
                        <p className="text-sm text-muted-foreground">
                            The passage was created and now lives in the Reading
                            section — open the Reading tab to add its questions.
                        </p>
                    )}

                    <label className="flex cursor-pointer flex-col items-center gap-2 rounded-xl border border-dashed border-border px-6 py-8 text-center transition-colors focus-within:ring-[3px] focus-within:ring-ring/50 hover:border-primary/40">
                        <span
                            aria-hidden
                            className="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
                        >
                            <CloudUpload className="size-5" />
                        </span>
                        <span className="text-sm font-medium text-mono">
                            Click to choose a PDF
                        </span>
                        <span className="text-xs text-muted-foreground">
                            The text is extracted for your review first
                        </span>
                        <input
                            type="file"
                            accept=".pdf,application/pdf"
                            className="sr-only"
                            onChange={chooseFile}
                        />
                    </label>

                    <div className="flex flex-wrap items-center gap-3">
                        {file && (
                            <p className="min-w-0 text-sm text-secondary-foreground">
                                <span className="font-medium break-all text-mono">
                                    {file.name}
                                </span>{' '}
                                <span className="text-muted-foreground">
                                    · {formatFileSize(file.size)}
                                </span>
                            </p>
                        )}
                        <Button
                            type="button"
                            variant="outline"
                            disabled={!file || extracting}
                            onClick={extractPreview}
                        >
                            {extracting ? (
                                <>
                                    <Loader2
                                        aria-hidden
                                        className="size-4 animate-spin"
                                    />
                                    Extracting…
                                </>
                            ) : (
                                'Extract text preview'
                            )}
                        </Button>
                    </div>

                    {extractError && (
                        <p className="text-sm text-destructive">
                            {extractError}
                        </p>
                    )}

                    {preview !== null && (
                        <form
                            onSubmit={createPassage}
                            className="space-y-4 border-t border-border pt-4"
                        >
                            <Badge
                                variant="secondary"
                                className="border-transparent bg-amber-500/10 text-amber-700 dark:text-amber-400"
                            >
                                Preview — review before creating content
                            </Badge>

                            <div className="space-y-1.5">
                                <Label htmlFor="pdf-passage-title">
                                    Passage title
                                </Label>
                                <Input
                                    id="pdf-passage-title"
                                    value={createForm.data.title}
                                    onChange={(e) =>
                                        createForm.setData(
                                            'title',
                                            e.target.value,
                                        )
                                    }
                                />
                                <FieldError message={createForm.errors.title} />
                            </div>

                            <div className="space-y-1.5">
                                <Label htmlFor="pdf-passage-body">
                                    Extracted text (editable)
                                </Label>
                                <Textarea
                                    id="pdf-passage-body"
                                    className="min-h-48"
                                    value={createForm.data.body}
                                    onChange={(e) =>
                                        createForm.setData(
                                            'body',
                                            e.target.value,
                                        )
                                    }
                                />
                                <FieldError message={createForm.errors.body} />
                            </div>

                            <SaveButton
                                type="submit"
                                processing={createForm.processing}
                                recentlySuccessful={
                                    createForm.recentlySuccessful
                                }
                            >
                                Create passage from this text
                            </SaveButton>
                        </form>
                    )}
                </div>
            </SectionCard>
        </div>
    );
}
