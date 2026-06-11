import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import {
    buttonPrimaryClass,
    buttonSecondaryClass,
    Field,
    inputClass,
    StatusBanner,
    type Option,
} from '../components';

interface TutorMaterialCounts {
    draft: number;
    publishing: number;
    published: number;
    publish_failed: number;
    archived: number;
}

interface TutorMaterials {
    counts: TutorMaterialCounts;
    rebuild_available: boolean;
}

interface TutorOperationalBounds {
    min: number;
    max: number;
}

interface TutorOperationalSettings {
    defaults: Record<string, number>;
    effective: Record<string, number>;
    bounds: Record<string, TutorOperationalBounds>;
}

interface SettingsEditProps {
    sections: Option[];
    defaults: Record<string, number>;
    effective: Record<string, number>;
    bounds: { min: number; max: number };
    tutorOperational: TutorOperationalSettings;
    tutorMaterials: TutorMaterials;
    status?: string | null;
}

function minutes(seconds: number): string {
    return (seconds / 60).toFixed(seconds % 60 === 0 ? 0 : 1);
}

const MATERIAL_ROWS: {
    key: keyof TutorMaterialCounts;
    label: string;
}[] = [
    { key: 'published', label: 'Available to the AI Tutor' },
    { key: 'publishing', label: 'Being published right now' },
    { key: 'publish_failed', label: 'Failed to publish' },
    { key: 'draft', label: 'Draft — not published yet' },
    { key: 'archived', label: 'Archived — no longer available' },
];

function TutorMaterialsCard({
    tutorMaterials,
}: {
    tutorMaterials: TutorMaterials;
}) {
    const [rebuilding, setRebuilding] = useState(false);
    const failed = tutorMaterials.counts.publish_failed;

    const rebuild = () => {
        router.post(
            '/admin/curriculum-index/rebuild',
            {},
            {
                preserveScroll: true,
                onStart: () => setRebuilding(true),
                onFinish: () => setRebuilding(false),
            },
        );
    };

    return (
        <section className="max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-5">
            <div>
                <h2 className="text-base font-semibold text-slate-900">
                    AI Tutor materials
                </h2>
                <p className="mt-1 text-sm text-slate-600">
                    Documents the AI Tutor can use when helping students.
                    Documents are uploaded and published on the Curriculum page.
                </p>
            </div>

            <ul className="divide-y divide-slate-100 rounded-md border border-slate-200 text-sm">
                {MATERIAL_ROWS.map((row) => {
                    const count = tutorMaterials.counts[row.key];
                    const isFailureRow = row.key === 'publish_failed';

                    return (
                        <li
                            key={row.key}
                            className="flex items-center justify-between gap-3 px-3 py-2"
                        >
                            <span
                                className={
                                    isFailureRow && count > 0
                                        ? 'font-medium text-red-700'
                                        : 'text-slate-600'
                                }
                            >
                                {row.label}
                            </span>
                            <span
                                className={
                                    isFailureRow && count > 0
                                        ? 'font-semibold text-red-700'
                                        : 'font-semibold text-slate-900'
                                }
                            >
                                {count}
                            </span>
                        </li>
                    );
                })}
            </ul>

            {failed > 0 && (
                <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {failed} {failed === 1 ? 'document' : 'documents'} failed to
                    publish —{' '}
                    <Link
                        href="/staff/curriculum"
                        className="font-medium underline"
                    >
                        open Curriculum
                    </Link>{' '}
                    to retry.
                </div>
            )}

            <div className="space-y-2 border-t border-slate-100 pt-4">
                <p className="text-sm text-slate-600">
                    Safe to run at any time: it simply sends every published
                    document to the AI Tutor again. Use it after a connection
                    problem, or if the AI Tutor seems to be missing materials.
                </p>
                {tutorMaterials.rebuild_available ? (
                    <button
                        type="button"
                        onClick={rebuild}
                        disabled={rebuilding}
                        className={buttonSecondaryClass}
                    >
                        {rebuilding
                            ? 'Re-publishing…'
                            : 'Re-publish all documents to the AI Tutor'}
                    </button>
                ) : (
                    <p className="text-sm text-slate-500">
                        This tool isn't available yet.
                    </p>
                )}
            </div>
        </section>
    );
}

export default function SettingsEdit({
    sections,
    defaults,
    effective,
    bounds,
    tutorOperational,
    tutorMaterials,
    status,
}: SettingsEditProps) {
    const form = useForm<{
        section_time_limits: Record<string, string>;
        tutor_operational: Record<string, string>;
    }>({
        section_time_limits: Object.fromEntries(
            sections.map((section) => [
                section.value,
                String(effective[section.value] ?? ''),
            ]),
        ),
        tutor_operational: {
            rotation_threshold_pairs: String(
                tutorOperational.effective.rotation_threshold_pairs,
            ),
            rotation_summarize_pairs: String(
                tutorOperational.effective.rotation_summarize_pairs,
            ),
            violation_notification_threshold: String(
                tutorOperational.effective.violation_notification_threshold,
            ),
            violation_notification_window_days: String(
                tutorOperational.effective.violation_notification_window_days,
            ),
        },
    });

    const errors = form.errors as Record<string, string>;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put('/admin/settings', { preserveScroll: true });
    };

    return (
        <GlcLayout title="Settings">
            <Head title="Settings" />

            <StatusBanner message={status} />

            <div className="space-y-6">
                <form
                    onSubmit={submit}
                    className="max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-5"
                >
                    <div>
                        <h2 className="text-base font-semibold text-slate-900">
                            Placement test timing
                        </h2>
                        <p className="mt-1 text-sm text-slate-600">
                            How long candidates get for each section of the
                            placement test, in seconds. Allowed range:{' '}
                            {bounds.min}–{bounds.max} seconds (
                            {minutes(bounds.min)}–{minutes(bounds.max)}{' '}
                            minutes). Changes only affect tests started after
                            you save.
                        </p>
                    </div>

                    {sections.map((section) => (
                        <Field
                            key={section.value}
                            label={section.label}
                            htmlFor={`limit-${section.value}`}
                            error={
                                errors[`section_time_limits.${section.value}`]
                            }
                            hint={`Time allowed for the ${section.label} section. Standard: ${defaults[section.value]} seconds (${minutes(defaults[section.value])} min). Currently: ${effective[section.value]} seconds.`}
                        >
                            <input
                                id={`limit-${section.value}`}
                                type="number"
                                min={bounds.min}
                                max={bounds.max}
                                value={
                                    form.data.section_time_limits[section.value]
                                }
                                onChange={(e) =>
                                    form.setData('section_time_limits', {
                                        ...form.data.section_time_limits,
                                        [section.value]: e.target.value,
                                    })
                                }
                                className={inputClass}
                                required
                            />
                        </Field>
                    ))}

                    <div className="border-t border-slate-100 pt-4">
                        <h3 className="text-sm font-semibold text-slate-900">
                            AI Tutor behaviour
                        </h3>
                        <p className="mt-1 text-sm text-slate-600">
                            Conversation rotation and teacher alerts for
                            repeated homework-answer requests.
                        </p>
                        <div className="mt-4 space-y-4">
                            <Field
                                label="Rotation threshold (message pairs)"
                                htmlFor="rotation-threshold"
                                error={
                                    errors[
                                        'tutor_operational.rotation_threshold_pairs'
                                    ]
                                }
                                hint={`Summarize older messages after this many student/tutor pairs. Default: ${tutorOperational.defaults.rotation_threshold_pairs}.`}
                            >
                                <input
                                    id="rotation-threshold"
                                    type="number"
                                    min={
                                        tutorOperational.bounds
                                            .rotation_threshold_pairs.min
                                    }
                                    max={
                                        tutorOperational.bounds
                                            .rotation_threshold_pairs.max
                                    }
                                    value={
                                        form.data.tutor_operational
                                            .rotation_threshold_pairs
                                    }
                                    onChange={(e) =>
                                        form.setData('tutor_operational', {
                                            ...form.data.tutor_operational,
                                            rotation_threshold_pairs:
                                                e.target.value,
                                        })
                                    }
                                    className={inputClass}
                                    required
                                />
                            </Field>
                            <Field
                                label="Pairs to summarize per rotation"
                                htmlFor="rotation-summarize"
                                error={
                                    errors[
                                        'tutor_operational.rotation_summarize_pairs'
                                    ]
                                }
                                hint={`Default: ${tutorOperational.defaults.rotation_summarize_pairs}.`}
                            >
                                <input
                                    id="rotation-summarize"
                                    type="number"
                                    min={
                                        tutorOperational.bounds
                                            .rotation_summarize_pairs.min
                                    }
                                    max={
                                        tutorOperational.bounds
                                            .rotation_summarize_pairs.max
                                    }
                                    value={
                                        form.data.tutor_operational
                                            .rotation_summarize_pairs
                                    }
                                    onChange={(e) =>
                                        form.setData('tutor_operational', {
                                            ...form.data.tutor_operational,
                                            rotation_summarize_pairs:
                                                e.target.value,
                                        })
                                    }
                                    className={inputClass}
                                    required
                                />
                            </Field>
                            <Field
                                label="Direct-answer alert threshold"
                                htmlFor="violation-threshold"
                                error={
                                    errors[
                                        'tutor_operational.violation_notification_threshold'
                                    ]
                                }
                                hint={`Notify linked teachers after this many direct-answer requests within the window. Default: ${tutorOperational.defaults.violation_notification_threshold}.`}
                            >
                                <input
                                    id="violation-threshold"
                                    type="number"
                                    min={
                                        tutorOperational.bounds
                                            .violation_notification_threshold
                                            .min
                                    }
                                    max={
                                        tutorOperational.bounds
                                            .violation_notification_threshold
                                            .max
                                    }
                                    value={
                                        form.data.tutor_operational
                                            .violation_notification_threshold
                                    }
                                    onChange={(e) =>
                                        form.setData('tutor_operational', {
                                            ...form.data.tutor_operational,
                                            violation_notification_threshold:
                                                e.target.value,
                                        })
                                    }
                                    className={inputClass}
                                    required
                                />
                            </Field>
                            <Field
                                label="Direct-answer alert window (days)"
                                htmlFor="violation-window"
                                error={
                                    errors[
                                        'tutor_operational.violation_notification_window_days'
                                    ]
                                }
                                hint={`Default: ${tutorOperational.defaults.violation_notification_window_days} days.`}
                            >
                                <input
                                    id="violation-window"
                                    type="number"
                                    min={
                                        tutorOperational.bounds
                                            .violation_notification_window_days
                                            .min
                                    }
                                    max={
                                        tutorOperational.bounds
                                            .violation_notification_window_days
                                            .max
                                    }
                                    value={
                                        form.data.tutor_operational
                                            .violation_notification_window_days
                                    }
                                    onChange={(e) =>
                                        form.setData('tutor_operational', {
                                            ...form.data.tutor_operational,
                                            violation_notification_window_days:
                                                e.target.value,
                                        })
                                    }
                                    className={inputClass}
                                    required
                                />
                            </Field>
                        </div>
                    </div>

                    <div className="flex justify-end">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className={buttonPrimaryClass}
                        >
                            Save settings
                        </button>
                    </div>
                </form>

                <TutorMaterialsCard tutorMaterials={tutorMaterials} />

                <section className="max-w-xl space-y-2 rounded-lg border border-slate-200 bg-white p-5">
                    <h2 className="text-base font-semibold text-slate-900">
                        AI Models
                    </h2>
                    <p className="text-sm text-slate-600">
                        Choose the AI provider and model for placement writing
                        evaluation, speaking transcription and evaluation, AI
                        Tutor chat and writing correction, compare pricing, and
                        manage provider API keys.
                    </p>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href="/admin/settings/ai"
                            className={buttonSecondaryClass}
                        >
                            Open AI Models settings
                        </Link>
                        <Link
                            href="/admin/settings/writing-guidelines"
                            className={buttonSecondaryClass}
                        >
                            Writing guidelines
                        </Link>
                        <Link
                            href="/admin/settings/speaking-guidelines"
                            className={buttonSecondaryClass}
                        >
                            Speaking guidelines
                        </Link>
                    </div>
                </section>
            </div>
        </GlcLayout>
    );
}
