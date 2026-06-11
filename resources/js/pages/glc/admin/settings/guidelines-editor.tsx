import GlcLayout from '@/layouts/glc-layout';
import ai from '@/routes/admin/settings/ai';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import {
    Badge,
    buttonDangerClass,
    buttonPrimaryClass,
    buttonSecondaryClass,
    ConfirmDialog,
    inputClass,
    StatusBanner,
} from '../components';

export interface Criterion {
    title: string;
    description: string;
}

export interface GuidelineLimits {
    max_criteria: number;
    max_title_length: number;
    max_description_length: number;
}

export interface GuidelinesPageProps {
    criteria: Criterion[];
    defaults: Criterion[];
    isCustomized: boolean;
    limits: GuidelineLimits;
    status?: string | null;
}

interface GuidelinesEditorProps extends GuidelinesPageProps {
    pageTitle: string;
    skillLabel: string;
    intro: string;
    resetMessage: string;
    updateUrl: string;
    resetUrl: string;
}

function promptBlock(criteria: Criterion[]): string {
    return criteria
        .map(
            (criterion, index) =>
                `${index + 1}. ${criterion.title}: ${criterion.description}`,
        )
        .join('\n');
}

function CriterionCard({
    index,
    total,
    criterion,
    limits,
    errors,
    canRemove,
    onChange,
    onMove,
    onRemove,
}: {
    index: number;
    total: number;
    criterion: Criterion;
    limits: GuidelineLimits;
    errors: Record<string, string>;
    canRemove: boolean;
    onChange: (field: keyof Criterion, value: string) => void;
    onMove: (direction: -1 | 1) => void;
    onRemove: () => void;
}) {
    const titleError = errors[`criteria.${index}.title`];
    const descriptionError = errors[`criteria.${index}.description`];

    return (
        <li className="space-y-3 rounded-md border border-slate-200 p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="text-sm font-semibold text-slate-800">
                    Criterion {index + 1}
                </span>
                <div className="flex items-center gap-1">
                    <button
                        type="button"
                        onClick={() => onMove(-1)}
                        disabled={index === 0}
                        className="rounded-md border border-slate-300 px-2 py-1 text-sm text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        aria-label={`Move criterion ${index + 1} up`}
                    >
                        Up
                    </button>
                    <button
                        type="button"
                        onClick={() => onMove(1)}
                        disabled={index === total - 1}
                        className="rounded-md border border-slate-300 px-2 py-1 text-sm text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        aria-label={`Move criterion ${index + 1} down`}
                    >
                        Down
                    </button>
                    <button
                        type="button"
                        onClick={onRemove}
                        disabled={!canRemove}
                        className="rounded-md border border-red-200 px-2 py-1 text-sm text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                        aria-label={`Remove criterion ${index + 1}`}
                    >
                        Remove
                    </button>
                </div>
            </div>

            <div className="space-y-1">
                <label
                    htmlFor={`criterion-${index}-title`}
                    className="block text-sm font-medium text-slate-700"
                >
                    Title
                </label>
                <input
                    id={`criterion-${index}-title`}
                    type="text"
                    value={criterion.title}
                    maxLength={limits.max_title_length}
                    onChange={(e) => onChange('title', e.target.value)}
                    className={inputClass}
                    placeholder="e.g. Grammar accuracy"
                />
                {titleError && (
                    <p className="text-xs text-red-600">{titleError}</p>
                )}
            </div>

            <div className="space-y-1">
                <label
                    htmlFor={`criterion-${index}-description`}
                    className="block text-sm font-medium text-slate-700"
                >
                    Description
                </label>
                <textarea
                    id={`criterion-${index}-description`}
                    value={criterion.description}
                    maxLength={limits.max_description_length}
                    onChange={(e) => onChange('description', e.target.value)}
                    rows={3}
                    className={inputClass}
                    placeholder="What should the AI look for under this criterion?"
                />
                {descriptionError && (
                    <p className="text-xs text-red-600">{descriptionError}</p>
                )}
            </div>
        </li>
    );
}

export default function GuidelinesEditor({
    pageTitle,
    skillLabel,
    intro,
    resetMessage,
    updateUrl,
    resetUrl,
    criteria,
    isCustomized,
    limits,
    status,
}: GuidelinesEditorProps) {
    const form = useForm<{ criteria: Criterion[] }>({ criteria });
    const [confirmingReset, setConfirmingReset] = useState(false);
    const [resetting, setResetting] = useState(false);

    const errors = form.errors as Record<string, string>;
    const listError = errors['criteria'];
    const list = form.data.criteria;
    const atMax = list.length >= limits.max_criteria;

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

    const move = (index: number, direction: -1 | 1) => {
        const target = index + direction;

        if (target < 0 || target >= list.length) {
            return;
        }

        const next = [...list];
        [next[index], next[target]] = [next[target], next[index]];
        form.setData('criteria', next);
    };

    const remove = (index: number) => {
        form.setData(
            'criteria',
            list.filter((_, i) => i !== index),
        );
    };

    const add = () => {
        if (!atMax) {
            form.setData('criteria', [
                ...list,
                { title: '', description: '' },
            ]);
        }
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put(updateUrl, { preserveScroll: true });
    };

    const reset = () => {
        router.delete(resetUrl, {
            preserveScroll: true,
            onStart: () => setResetting(true),
            onFinish: () => {
                setResetting(false);
                setConfirmingReset(false);
            },
        });
    };

    return (
        <GlcLayout title={pageTitle}>
            <Head title={pageTitle} />

            <StatusBanner message={status} />

            <div className="space-y-6">
                <div className="space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="text-base font-semibold text-slate-900">
                            {skillLabel} evaluation guidelines
                        </h2>
                        {isCustomized ? (
                            <Badge tone="blue">Customized</Badge>
                        ) : (
                            <Badge tone="slate">Using defaults</Badge>
                        )}
                    </div>
                    <p className="text-sm text-slate-600">{intro}</p>
                    <p className="text-sm text-slate-600">
                        Which AI model runs the evaluation is configured on the{' '}
                        <Link
                            href={ai.edit.url()}
                            className="font-medium text-emerald-700 underline hover:text-emerald-600"
                        >
                            AI Models settings
                        </Link>{' '}
                        page.
                    </p>
                </div>

                <form
                    onSubmit={submit}
                    className="space-y-4 rounded-lg border border-slate-200 bg-white p-5"
                >
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h3 className="text-sm font-semibold text-slate-800">
                            Criteria
                        </h3>
                        <span className="text-xs text-slate-500">
                            {list.length} of {limits.max_criteria} criteria
                        </span>
                    </div>

                    {listError && (
                        <p className="text-sm text-red-600">{listError}</p>
                    )}

                    <ul className="space-y-3">
                        {list.map((criterion, index) => (
                            <CriterionCard
                                key={index}
                                index={index}
                                total={list.length}
                                criterion={criterion}
                                limits={limits}
                                errors={errors}
                                canRemove={list.length > 1}
                                onChange={(field, value) =>
                                    setCriterion(index, field, value)
                                }
                                onMove={(direction) => move(index, direction)}
                                onRemove={() => remove(index)}
                            />
                        ))}
                    </ul>

                    <div className="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
                        <button
                            type="button"
                            onClick={add}
                            disabled={atMax}
                            className={buttonSecondaryClass}
                        >
                            Add criterion
                        </button>
                        <div className="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                onClick={() => setConfirmingReset(true)}
                                disabled={!isCustomized || resetting}
                                className={buttonDangerClass}
                            >
                                Reset to defaults
                            </button>
                            <button
                                type="submit"
                                disabled={form.processing}
                                className={buttonPrimaryClass}
                            >
                                {form.processing
                                    ? 'Saving…'
                                    : 'Save guidelines'}
                            </button>
                        </div>
                    </div>
                </form>

                <section className="space-y-3 rounded-lg border border-slate-200 bg-white p-5">
                    <div>
                        <h3 className="text-sm font-semibold text-slate-800">
                            Prompt preview
                        </h3>
                        <p className="mt-1 text-sm text-slate-600">
                            The numbered block below is exactly what the AI
                            receives, built live from the form above. Save to
                            apply it.
                        </p>
                    </div>
                    <pre className="overflow-x-auto rounded-md border border-slate-200 bg-slate-50 p-4 text-xs leading-relaxed whitespace-pre-wrap text-slate-800">
                        {promptBlock(list) ||
                            'Add at least one criterion to build the prompt block.'}
                    </pre>
                </section>
            </div>

            <ConfirmDialog
                open={confirmingReset}
                title="Reset to defaults?"
                message={resetMessage}
                confirmLabel={resetting ? 'Resetting…' : 'Reset to defaults'}
                danger
                processing={resetting}
                onConfirm={reset}
                onCancel={() => setConfirmingReset(false)}
            />
        </GlcLayout>
    );
}
