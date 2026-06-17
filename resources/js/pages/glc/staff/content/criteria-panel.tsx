import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { router, useForm } from '@inertiajs/react';
import { ListChecks, Pencil, RotateCcw, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    AddTile,
    ConfirmDeleteDialog,
    FieldError,
    SaveButton,
    SectionCard,
} from './shared';

export interface Criterion {
    title: string;
    description: string;
}

export interface CriteriaData {
    criteria: Criterion[];
    defaults: Criterion[];
    isCustomized: boolean;
    limits: {
        max_criteria: number;
        max_title_length: number;
        max_description_length: number;
    };
}

const CRITERIA_URL = '/staff/placement-content/criteria';

/**
 * Marking criteria card shown on the Writing and Speaking tabs. The criteria
 * feed the AI provisional review prompts (same storage as the Admin
 * guidelines settings); staff always make the final decision.
 */
export function CriteriaPanel({
    skill,
    data,
}: {
    skill: 'writing' | 'speaking';
    data: CriteriaData;
}) {
    const [editing, setEditing] = useState(false);
    const [resetting, setResetting] = useState(false);

    const reset = () => {
        router.delete(`${CRITERIA_URL}/${skill}`, {
            preserveScroll: true,
            onStart: () => setResetting(true),
            onFinish: () => {
                setResetting(false);
                setEditing(false);
            },
        });
    };

    return (
        <SectionCard
            icon={ListChecks}
            title="Marking criteria"
            description="The AI provisional review scores each submission against these criteria. Staff always make the final decision."
            toolbar={
                <div className="flex items-center gap-2">
                    {data.isCustomized ? (
                        <Badge variant="outline" className="text-2xs">
                            Customized
                        </Badge>
                    ) : (
                        <Badge
                            variant="outline"
                            className="text-2xs text-muted-foreground"
                        >
                            GLC defaults
                        </Badge>
                    )}
                    {!editing && (
                        <Button
                            size="sm"
                            variant="outline"
                            onClick={() => setEditing(true)}
                        >
                            <Pencil aria-hidden className="size-3.5" />
                            Edit
                        </Button>
                    )}
                </div>
            }
        >
            {editing ? (
                <CriteriaEditor
                    skill={skill}
                    data={data}
                    resetting={resetting}
                    onClose={() => setEditing(false)}
                    onReset={reset}
                />
            ) : (
                <ol className="space-y-3">
                    {data.criteria.map((criterion, index) => (
                        <li key={index} className="flex gap-3">
                            <span
                                aria-hidden
                                className="flex h-6 min-w-6 shrink-0 items-center justify-center rounded-md bg-muted px-1 text-xs font-semibold text-muted-foreground"
                            >
                                {index + 1}
                            </span>
                            <div className="min-w-0 space-y-0.5">
                                <p className="text-sm font-medium text-mono">
                                    {criterion.title}
                                </p>
                                <p className="text-sm text-secondary-foreground">
                                    {criterion.description}
                                </p>
                            </div>
                        </li>
                    ))}
                </ol>
            )}
        </SectionCard>
    );
}

function CriteriaEditor({
    skill,
    data,
    resetting,
    onClose,
    onReset,
}: {
    skill: 'writing' | 'speaking';
    data: CriteriaData;
    resetting: boolean;
    onClose: () => void;
    onReset: () => void;
}) {
    const form = useForm<{ criteria: Criterion[] }>({
        criteria: data.criteria,
    });

    const errors = form.errors as Record<string, string>;
    const list = form.data.criteria;
    const atMax = list.length >= data.limits.max_criteria;

    const setCriterion = (
        index: number,
        field: keyof Criterion,
        value: string,
    ) => {
        form.setData(
            'criteria',
            list.map((criterion, i) =>
                i === index ? { ...criterion, [field]: value } : criterion,
            ),
        );
    };

    const remove = (index: number) => {
        form.setData(
            'criteria',
            list.filter((_, i) => i !== index),
        );
    };

    const submit = () => {
        form.put(`${CRITERIA_URL}/${skill}`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between gap-2">
                <span className="text-xs text-muted-foreground">
                    {list.length} of {data.limits.max_criteria} criteria
                </span>
            </div>

            <FieldError message={errors.criteria} />

            <ul className="space-y-3">
                {list.map((criterion, index) => (
                    <li
                        key={index}
                        className="space-y-2.5 rounded-xl border border-border bg-card p-4"
                    >
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                Criterion {index + 1}
                            </span>
                            <button
                                type="button"
                                disabled={list.length <= 1}
                                onClick={() => remove(index)}
                                className="rounded-md p-1 text-muted-foreground transition-colors hover:text-destructive focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                <Trash2 aria-hidden className="size-4" />
                                <span className="sr-only">
                                    Remove criterion {index + 1}
                                </span>
                            </button>
                        </div>
                        <div className="space-y-1.5">
                            <Label
                                htmlFor={`${skill}-criterion-${index}-title`}
                            >
                                Title
                            </Label>
                            <Input
                                id={`${skill}-criterion-${index}-title`}
                                value={criterion.title}
                                maxLength={data.limits.max_title_length}
                                placeholder="e.g. Task achievement"
                                onChange={(e) =>
                                    setCriterion(index, 'title', e.target.value)
                                }
                            />
                            <FieldError
                                message={errors[`criteria.${index}.title`]}
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label
                                htmlFor={`${skill}-criterion-${index}-description`}
                            >
                                Description
                            </Label>
                            <Textarea
                                id={`${skill}-criterion-${index}-description`}
                                rows={3}
                                value={criterion.description}
                                maxLength={data.limits.max_description_length}
                                placeholder="What should the AI look for under this criterion?"
                                onChange={(e) =>
                                    setCriterion(
                                        index,
                                        'description',
                                        e.target.value,
                                    )
                                }
                            />
                            <FieldError
                                message={
                                    errors[`criteria.${index}.description`]
                                }
                            />
                        </div>
                    </li>
                ))}
            </ul>

            {!atMax && (
                <AddTile
                    onClick={() =>
                        form.setData('criteria', [
                            ...list,
                            { title: '', description: '' },
                        ])
                    }
                    label="Add criterion"
                />
            )}

            <div className="flex flex-wrap items-center gap-2.5">
                <SaveButton
                    size="sm"
                    processing={form.processing}
                    recentlySuccessful={form.recentlySuccessful}
                    onClick={submit}
                >
                    Save criteria
                </SaveButton>
                <Button size="sm" variant="ghost" onClick={onClose}>
                    Cancel
                </Button>
                {data.isCustomized && (
                    <ConfirmDeleteDialog
                        title="Reset to the GLC defaults?"
                        description="This discards every customized criterion and restores the standard GLC rubric. The change applies to new AI provisional reviews immediately."
                        confirmLabel={
                            resetting ? 'Resetting…' : 'Reset to defaults'
                        }
                        trigger={
                            <Button
                                size="sm"
                                variant="ghost"
                                className="ms-auto text-muted-foreground hover:text-destructive"
                            >
                                <RotateCcw aria-hidden className="size-4" />
                                Reset to defaults
                            </Button>
                        }
                        onConfirm={onReset}
                    />
                )}
            </div>
        </div>
    );
}
