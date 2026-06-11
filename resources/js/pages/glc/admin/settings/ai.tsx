import GlcLayout from '@/layouts/glc-layout';
import keys from '@/routes/admin/settings/ai/keys';
import selection from '@/routes/admin/settings/ai/selection';
import speakingGuidelines from '@/routes/admin/settings/speaking-guidelines';
import writingGuidelines from '@/routes/admin/settings/writing-guidelines';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import {
    Badge,
    buttonDangerClass,
    buttonPrimaryClass,
    inputClass,
    StatusBanner,
} from '../components';

type Tier = 'flagship' | 'balanced' | 'value' | 'budget';

interface CatalogModel {
    label: string;
    tier: Tier;
    notes: string;
    input_per_mtok?: number;
    output_per_mtok?: number;
    per_minute?: number;
}

interface CatalogProvider {
    label: string;
    pricing_url: string;
    credential: string;
    models: Record<string, CatalogModel>;
}

interface TaskConfig {
    label: string;
    providers: Record<string, CatalogProvider>;
    selection: { provider: string; model: string };
    configured: boolean;
}

interface CredentialStatus {
    stored: boolean;
    masked: string | null;
    env_fallback: boolean;
}

interface CredentialRow {
    credential: string;
    provider_labels: string[];
    status: CredentialStatus;
}

interface AiSettingsProps {
    tasks: Record<string, TaskConfig>;
    credentials: CredentialRow[];
    pricing_retrieved_at: string;
    status?: string | null;
}

const TIER_TONES: Record<Tier, 'blue' | 'green' | 'amber' | 'slate'> = {
    flagship: 'blue',
    balanced: 'green',
    value: 'amber',
    budget: 'slate',
};

/** Estimation assumptions for the per-candidate cost hint. */
const WRITING_INPUT_TOKENS = 1500;
const WRITING_OUTPUT_TOKENS = 500;
const SPEAKING_AUDIO_MINUTES = 3;

function money(value: number): string {
    if (value >= 0.01) {
        return `$${value.toFixed(2)}`;
    }

    return `$${value.toFixed(4).replace(/0+$/, '').replace(/\.$/, '')}`;
}

function pricingLine(model: CatalogModel): string {
    if (model.per_minute !== undefined) {
        return `${money(model.per_minute)} / audio minute`;
    }

    return `${money(model.input_per_mtok ?? 0)} in / ${money(model.output_per_mtok ?? 0)} out per 1M tokens`;
}

function estimatedCandidateCost(model: CatalogModel): number {
    if (model.per_minute !== undefined) {
        return model.per_minute * SPEAKING_AUDIO_MINUTES;
    }

    return (
        ((model.input_per_mtok ?? 0) * WRITING_INPUT_TOKENS) / 1_000_000 +
        ((model.output_per_mtok ?? 0) * WRITING_OUTPUT_TOKENS) / 1_000_000
    );
}

function estimateBasis(model: CatalogModel): string {
    if (model.per_minute !== undefined) {
        return `assumes ~${SPEAKING_AUDIO_MINUTES} audio minutes`;
    }

    return `assumes ~${WRITING_INPUT_TOKENS.toLocaleString()} input + ${WRITING_OUTPUT_TOKENS.toLocaleString()} output tokens`;
}

function ModelRow({
    task,
    providerKey,
    modelKey,
    model,
    active,
    onSelect,
    selecting,
}: {
    task: string;
    providerKey: string;
    modelKey: string;
    model: CatalogModel;
    active: boolean;
    onSelect: () => void;
    selecting: boolean;
}) {
    return (
        <li
            className={`space-y-1.5 px-3 py-3 ${active ? 'bg-emerald-50/60' : ''}`}
        >
            <div className="flex flex-wrap items-center gap-2">
                <span className="text-sm font-medium text-slate-900">
                    {model.label}
                </span>
                <Badge tone={TIER_TONES[model.tier] ?? 'slate'}>
                    {model.tier}
                </Badge>
                {active && <Badge tone="green">Currently active</Badge>}
            </div>
            <p className="text-sm text-slate-700">
                {pricingLine(model)}
                <span className="text-slate-500">
                    {' '}
                    · est. {money(estimatedCandidateCost(model))} per candidate
                    ({estimateBasis(model)})
                </span>
            </p>
            <p className="text-xs text-slate-500">{model.notes}</p>
            {!active && (
                <button
                    type="button"
                    onClick={onSelect}
                    disabled={selecting}
                    className="text-sm font-medium text-emerald-700 hover:text-emerald-600 disabled:cursor-not-allowed disabled:opacity-50"
                    aria-label={`Use ${model.label} for ${task} (${providerKey}/${modelKey})`}
                >
                    {selecting ? 'Saving…' : 'Use this model'}
                </button>
            )}
        </li>
    );
}

function TaskSection({ task, config }: { task: string; config: TaskConfig }) {
    const [selectingKey, setSelectingKey] = useState<string | null>(null);

    const select = (provider: string, model: string) => {
        const key = `${provider}/${model}`;

        router.put(
            selection.update.url(),
            { task, provider, model },
            {
                preserveScroll: true,
                onStart: () => setSelectingKey(key),
                onFinish: () => setSelectingKey(null),
            },
        );
    };

    const activeProvider = config.providers[config.selection.provider];

    return (
        <section className="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
            <div>
                <h2 className="text-base font-semibold text-slate-900">
                    {config.label}
                </h2>
                <p className="mt-1 text-sm text-slate-600">
                    Currently using{' '}
                    <span className="font-medium text-slate-900">
                        {activeProvider?.models[config.selection.model]
                            ?.label ?? config.selection.model}
                    </span>{' '}
                    from {activeProvider?.label ?? config.selection.provider}.
                </p>
            </div>

            {!config.configured && (
                <div className="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    The selected provider (
                    {activeProvider?.label ?? config.selection.provider}) has no
                    usable API key. Add one in the API keys panel below, or pick
                    a provider that already has a key.
                </div>
            )}

            <div className="space-y-4">
                {Object.entries(config.providers).map(
                    ([providerKey, provider]) => (
                        <div
                            key={providerKey}
                            className="rounded-md border border-slate-200"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-3 py-2">
                                <span className="text-sm font-semibold text-slate-800">
                                    {provider.label}
                                </span>
                                <a
                                    href={provider.pricing_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="text-xs font-medium text-emerald-700 underline hover:text-emerald-600"
                                >
                                    Vendor pricing page
                                </a>
                            </div>
                            <ul className="divide-y divide-slate-100">
                                {Object.entries(provider.models).map(
                                    ([modelKey, model]) => (
                                        <ModelRow
                                            key={modelKey}
                                            task={task}
                                            providerKey={providerKey}
                                            modelKey={modelKey}
                                            model={model}
                                            active={
                                                config.selection.provider ===
                                                    providerKey &&
                                                config.selection.model ===
                                                    modelKey
                                            }
                                            selecting={
                                                selectingKey ===
                                                `${providerKey}/${modelKey}`
                                            }
                                            onSelect={() =>
                                                select(providerKey, modelKey)
                                            }
                                        />
                                    ),
                                )}
                            </ul>
                        </div>
                    ),
                )}
            </div>
        </section>
    );
}

function CredentialPanelRow({ row }: { row: CredentialRow }) {
    const form = useForm<{ credential: string; api_key: string }>({
        credential: row.credential,
        api_key: '',
    });
    const [removing, setRemoving] = useState(false);

    const save = (e: FormEvent) => {
        e.preventDefault();
        form.put(keys.update.url(), {
            preserveScroll: true,
            onSuccess: () => form.setData('api_key', ''),
        });
    };

    const remove = () => {
        router.put(
            keys.update.url(),
            { credential: row.credential, api_key: null },
            {
                preserveScroll: true,
                onStart: () => setRemoving(true),
                onFinish: () => setRemoving(false),
            },
        );
    };

    return (
        <li className="space-y-2 px-3 py-3">
            <div className="flex flex-wrap items-center gap-2">
                <span className="text-sm font-medium text-slate-900">
                    {row.provider_labels.join(' · ')}
                </span>
                {row.status.stored ? (
                    <Badge tone="green">{row.status.masked}</Badge>
                ) : row.status.env_fallback ? (
                    <Badge tone="blue">Using environment key</Badge>
                ) : (
                    <Badge tone="amber">No key</Badge>
                )}
            </div>

            <form onSubmit={save} className="flex flex-wrap items-center gap-2">
                <input
                    type="password"
                    autoComplete="off"
                    placeholder={
                        row.status.stored ? 'Replace API key' : 'Set API key'
                    }
                    value={form.data.api_key}
                    onChange={(e) => form.setData('api_key', e.target.value)}
                    className={`${inputClass} max-w-xs`}
                    aria-label={`API key for ${row.provider_labels.join(', ')}`}
                />
                <button
                    type="submit"
                    disabled={form.processing || form.data.api_key === ''}
                    className={buttonPrimaryClass}
                >
                    Save
                </button>
                {row.status.stored && (
                    <button
                        type="button"
                        onClick={remove}
                        disabled={removing}
                        className={buttonDangerClass}
                    >
                        {removing ? 'Removing…' : 'Remove'}
                    </button>
                )}
            </form>
            {form.errors.api_key && (
                <p className="text-xs text-red-600">{form.errors.api_key}</p>
            )}
        </li>
    );
}

export default function AiModelSettings({
    tasks,
    credentials,
    pricing_retrieved_at,
    status,
}: AiSettingsProps) {
    return (
        <GlcLayout title="AI Models">
            <Head title="AI Models" />

            <StatusBanner message={status} />

            <div className="space-y-6">
                <p className="text-sm text-slate-600">
                    Choose which AI provider and model power each placement test
                    task, and manage provider API keys. Prices are USD,
                    retrieved from official vendor pricing pages on{' '}
                    {pricing_retrieved_at}. The rubrics the evaluation models
                    judge against are managed on the{' '}
                    <Link
                        href={writingGuidelines.edit.url()}
                        className="font-medium text-emerald-700 underline hover:text-emerald-600"
                    >
                        Writing evaluation guidelines
                    </Link>{' '}
                    and{' '}
                    <Link
                        href={speakingGuidelines.edit.url()}
                        className="font-medium text-emerald-700 underline hover:text-emerald-600"
                    >
                        Speaking evaluation guidelines
                    </Link>{' '}
                    pages.
                </p>

                {Object.entries(tasks).map(([task, config]) => (
                    <TaskSection key={task} task={task} config={config} />
                ))}

                <section className="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
                    <div>
                        <h2 className="text-base font-semibold text-slate-900">
                            API keys
                        </h2>
                        <p className="mt-1 text-sm text-slate-600">
                            Keys are encrypted before they are stored and are
                            never shown again — only the last four characters.
                            Leave a provider blank to keep using the server
                            environment key, when one is configured.
                        </p>
                    </div>

                    <ul className="divide-y divide-slate-100 rounded-md border border-slate-200">
                        {credentials.map((row) => (
                            <CredentialPanelRow
                                key={row.credential}
                                row={row}
                            />
                        ))}
                    </ul>
                </section>
            </div>
        </GlcLayout>
    );
}
