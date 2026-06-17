import { GlcSettingsSidebarLayout } from '@/components/glc';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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

interface PlacementScoringBounds {
    min: number;
    max: number;
}

interface PlacementScoringSettings {
    defaults: {
        section_weights: Record<string, number>;
        level_band_minimums: Record<string, number>;
        variance_flag_threshold: number;
    };
    effective: {
        section_weights: Record<string, number>;
        level_band_minimums: Record<string, number>;
        variance_flag_threshold: number;
    };
    bounds: {
        section_weight: PlacementScoringBounds;
        level_band: PlacementScoringBounds;
        variance_flag_threshold: PlacementScoringBounds;
    };
    level_keys: string[];
}

interface SettingsEditProps {
    sections: Option[];
    defaults: Record<string, number>;
    effective: Record<string, number>;
    bounds: { min: number; max: number };
    tutorOperational: TutorOperationalSettings;
    placementScoring: PlacementScoringSettings;
    tutorMaterials: TutorMaterials;
    status?: string | null;
}

const SIDEBAR_SECTIONS = [
    { id: 'placement-timing', label: 'Placement test timing' },
    { id: 'placement-scoring', label: 'Placement scoring' },
    { id: 'tutor-behaviour', label: 'AI Tutor behaviour' },
    { id: 'tutor-materials', label: 'AI Tutor materials' },
    { id: 'ai-models', label: 'AI Models' },
] as const;

type SidebarSectionId = (typeof SIDEBAR_SECTIONS)[number]['id'];

const LEVEL_BAND_LABELS: Record<string, string> = {
    beginner: 'Beginner',
    elementary: 'Elementary',
    pre_intermediate: 'Pre-Intermediate',
    intermediate: 'Intermediate',
    upper_intermediate: 'Upper-Intermediate',
    advanced: 'Advanced',
};

function percentWeight(weight: number): string {
    return `${Math.round(weight * 1000) / 10}%`;
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
        <Card id="tutor-materials">
            <CardHeader>
                <CardTitle className="text-base">AI Tutor materials</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                <p className="text-sm text-secondary-foreground">
                    Documents the AI Tutor can use when helping students.
                    Documents are uploaded and published on the Curriculum page.
                </p>

                <ul className="divide-y divide-border rounded-md border border-border text-sm">
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
                                            ? 'font-medium text-destructive'
                                            : 'text-secondary-foreground'
                                    }
                                >
                                    {row.label}
                                </span>
                                <span
                                    className={
                                        isFailureRow && count > 0
                                            ? 'font-semibold text-destructive'
                                            : 'font-semibold text-mono'
                                    }
                                >
                                    {count}
                                </span>
                            </li>
                        );
                    })}
                </ul>

                {failed > 0 && (
                    <div className="rounded-md border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
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

                <div className="space-y-2 border-t border-border pt-4">
                    <p className="text-sm text-secondary-foreground">
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
                        <p className="text-sm text-muted-foreground">
                            This tool isn't available yet.
                        </p>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

export default function SettingsEdit({
    sections,
    defaults,
    effective,
    bounds,
    tutorOperational,
    placementScoring,
    tutorMaterials,
    status,
}: SettingsEditProps) {
    const [activeSection, setActiveSection] =
        useState<SidebarSectionId>('placement-timing');

    const form = useForm<{
        section_time_limits: Record<string, string>;
        tutor_operational: Record<string, string>;
        placement_scoring: {
            section_weights: Record<string, string>;
            level_band_minimums: Record<string, string>;
            variance_flag_threshold: string;
        };
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
        placement_scoring: {
            section_weights: Object.fromEntries(
                sections.map((section) => [
                    section.value,
                    String(
                        placementScoring.effective.section_weights[
                            section.value
                        ] ?? '',
                    ),
                ]),
            ),
            level_band_minimums: Object.fromEntries(
                placementScoring.level_keys.map((level) => [
                    level,
                    String(
                        placementScoring.effective.level_band_minimums[level] ??
                            '',
                    ),
                ]),
            ),
            variance_flag_threshold: String(
                placementScoring.effective.variance_flag_threshold,
            ),
        },
    });

    const errors = form.errors as Record<string, string>;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.put('/admin/settings', { preserveScroll: true });
    };

    const scrollToSection = (id: SidebarSectionId) => {
        setActiveSection(id);
        document.getElementById(id)?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    };

    return (
        <GlcLayout title="Settings">
            <Head title="Settings" />

            <StatusBanner message={status} />

            <GlcSettingsSidebarLayout
                items={SIDEBAR_SECTIONS.map((section) => ({
                    ...section,
                    active: activeSection === section.id,
                }))}
                onSelect={(id) => scrollToSection(id as SidebarSectionId)}
            >
                <form onSubmit={submit} className="space-y-6">
                    <Card id="placement-timing">
                        <CardContent className="pt-5 space-y-4">
                            <div>
                                <h2 className="text-base font-semibold text-mono">
                                    Placement test timing
                                </h2>
                                <p className="mt-1 text-sm text-secondary-foreground">
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
                        </CardContent>
                    </Card>

                    <Card id="placement-scoring">
                        <CardContent className="pt-5 space-y-4">
                            <div>
                                <h2 className="text-base font-semibold text-mono">
                                    Placement scoring
                                </h2>
                                <p className="mt-1 text-sm text-secondary-foreground">
                                    Section weights, GLC level bands, and the
                                    cross-section variance flag used during staff
                                    review. Missing Writing or Speaking scores are
                                    excluded and the remaining weights re-normalize.
                                </p>
                            </div>

                            <div>
                                <h3 className="text-sm font-semibold text-mono">
                                    Section weights
                                </h3>
                                <p className="mt-1 text-sm text-secondary-foreground">
                                    Decimal weights that must add up to 1.00 (for
                                    example, 0.20 = 20%).
                                </p>
                                {errors['placement_scoring.section_weights'] && (
                                    <p className="mt-2 text-sm text-destructive">
                                        {
                                            errors[
                                                'placement_scoring.section_weights'
                                            ]
                                        }
                                    </p>
                                )}
                                <div className="mt-4 space-y-4">
                                    {sections.map((section) => (
                                        <Field
                                            key={`weight-${section.value}`}
                                            label={`${section.label} weight`}
                                            htmlFor={`weight-${section.value}`}
                                            error={
                                                errors[
                                                    `placement_scoring.section_weights.${section.value}`
                                                ]
                                            }
                                            hint={`Default: ${percentWeight(placementScoring.defaults.section_weights[section.value])}. Allowed: ${placementScoring.bounds.section_weight.min}–${placementScoring.bounds.section_weight.max}.`}
                                        >
                                            <input
                                                id={`weight-${section.value}`}
                                                type="number"
                                                min={
                                                    placementScoring.bounds
                                                        .section_weight.min
                                                }
                                                max={
                                                    placementScoring.bounds
                                                        .section_weight.max
                                                }
                                                step="0.01"
                                                value={
                                                    form.data.placement_scoring
                                                        .section_weights[
                                                        section.value
                                                    ]
                                                }
                                                onChange={(e) =>
                                                    form.setData(
                                                        'placement_scoring',
                                                        {
                                                            ...form.data
                                                                .placement_scoring,
                                                            section_weights: {
                                                                ...form.data
                                                                    .placement_scoring
                                                                    .section_weights,
                                                                [section.value]:
                                                                    e.target
                                                                        .value,
                                                            },
                                                        },
                                                    )
                                                }
                                                className={inputClass}
                                                required
                                            />
                                        </Field>
                                    ))}
                                </div>
                            </div>

                            <div className="border-t border-border pt-4">
                                <h3 className="text-sm font-semibold text-mono">
                                    GLC level bands
                                </h3>
                                <p className="mt-1 text-sm text-secondary-foreground">
                                    Minimum composite percentage for each level.
                                    Starter is everything below Beginner.
                                </p>
                                {errors[
                                    'placement_scoring.level_band_minimums'
                                ] && (
                                    <p className="mt-2 text-sm text-destructive">
                                        {
                                            errors[
                                                'placement_scoring.level_band_minimums'
                                            ]
                                        }
                                    </p>
                                )}
                                <div className="mt-4 space-y-4">
                                    {placementScoring.level_keys.map((level) => (
                                        <Field
                                            key={`band-${level}`}
                                            label={`${LEVEL_BAND_LABELS[level] ?? level} minimum (%)`}
                                            htmlFor={`band-${level}`}
                                            error={
                                                errors[
                                                    `placement_scoring.level_band_minimums.${level}`
                                                ]
                                            }
                                            hint={`Default: ${placementScoring.defaults.level_band_minimums[level]}%.`}
                                        >
                                            <input
                                                id={`band-${level}`}
                                                type="number"
                                                min={
                                                    placementScoring.bounds
                                                        .level_band.min
                                                }
                                                max={
                                                    placementScoring.bounds
                                                        .level_band.max
                                                }
                                                step="0.1"
                                                value={
                                                    form.data.placement_scoring
                                                        .level_band_minimums[
                                                        level
                                                    ]
                                                }
                                                onChange={(e) =>
                                                    form.setData(
                                                        'placement_scoring',
                                                        {
                                                            ...form.data
                                                                .placement_scoring,
                                                            level_band_minimums:
                                                                {
                                                                    ...form.data
                                                                        .placement_scoring
                                                                        .level_band_minimums,
                                                                    [level]:
                                                                        e.target
                                                                            .value,
                                                                },
                                                        },
                                                    )
                                                }
                                                className={inputClass}
                                                required
                                            />
                                        </Field>
                                    ))}
                                </div>
                            </div>

                            <Field
                                label="Variance flag threshold (percentage points)"
                                htmlFor="variance-threshold"
                                error={
                                    errors[
                                        'placement_scoring.variance_flag_threshold'
                                    ]
                                }
                                hint={`Flag supervisor review when the spread between highest and lowest section scores reaches this value. Default: ${placementScoring.defaults.variance_flag_threshold}.`}
                            >
                                <input
                                    id="variance-threshold"
                                    type="number"
                                    min={
                                        placementScoring.bounds
                                            .variance_flag_threshold.min
                                    }
                                    max={
                                        placementScoring.bounds
                                            .variance_flag_threshold.max
                                    }
                                    step="0.1"
                                    value={
                                        form.data.placement_scoring
                                            .variance_flag_threshold
                                    }
                                    onChange={(e) =>
                                        form.setData('placement_scoring', {
                                            ...form.data.placement_scoring,
                                            variance_flag_threshold:
                                                e.target.value,
                                        })
                                    }
                                    className={inputClass}
                                    required
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    <Card id="tutor-behaviour">
                        <CardContent className="pt-5 space-y-4">
                            <div>
                                <h2 className="text-base font-semibold text-mono">
                                    AI Tutor behaviour
                                </h2>
                                <p className="mt-1 text-sm text-secondary-foreground">
                                    Conversation rotation and teacher alerts for
                                    repeated homework-answer requests.
                                </p>
                            </div>

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
                                            .violation_notification_threshold.min
                                    }
                                    max={
                                        tutorOperational.bounds
                                            .violation_notification_threshold.max
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
                        </CardContent>
                    </Card>

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

                <Card id="ai-models">
                    <CardHeader>
                        <CardTitle className="text-base">AI Models</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <p className="text-sm text-secondary-foreground">
                            Choose the AI provider and model for placement writing
                            evaluation, speaking transcription and evaluation, AI
                            Tutor chat and writing correction, compare pricing, and
                            manage provider API keys.
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Link
                                href="/admin/settings/ai-cost"
                                className={buttonSecondaryClass}
                            >
                                AI cost controls
                            </Link>
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
                    </CardContent>
                </Card>
            </GlcSettingsSidebarLayout>
        </GlcLayout>
    );
}
