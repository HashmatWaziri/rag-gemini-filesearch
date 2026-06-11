import GlcLayout from '@/layouts/glc-layout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    ATTENTION_LABELS,
    ProcessSteps,
    REVIEW_STATUS_LABELS,
    REVIEW_STATUS_TONES,
    sendUnlockMessage,
    TEST_TAKING_EVENT_LABELS,
    type PipelineInput,
    type ReviewStatus,
} from './process-steps';
import {
    Badge,
    btnPrimary,
    btnSecondary,
    Card,
    CheckIcon,
    DimensionScale,
    Field,
    inputCls,
    SECTION_LABELS,
    SECTION_ORDER,
    SparkIcon,
    xsrfToken,
} from './ui';

interface Question {
    id: number;
    body: string | null;
    options: string[] | null;
    correct_option: number | null;
    selected: number | null;
    is_correct: boolean | null;
}

interface AiDraft {
    status: string;
    dimension_scores: Record<string, number> | null;
    feedback: string | null;
    transcript: string | null;
    confidence: string | null;
    error: string | null;
    generated_at: string | null;
}

interface Narrative {
    strengths?: string | null;
    areas_to_improve?: string | null;
    recommendation?: string | null;
    next_steps?: string | null;
}

interface AiRecommendation {
    status: string;
    recommended_level: string | null;
    recommended_level_label: string | null;
    skill_levels: Record<string, string> | null;
    skill_summaries: Record<string, string> | null;
    confidence: string | null;
    rationale: string | null;
    error: string | null;
    generated_at: string | null;
}

interface PageProps {
    review: {
        id: number;
        status: ReviewStatus;
        status_label: string;
        flags: string[];
        assigned_to: number | null;
        assignee: string | null;
        is_assigned_to_me: boolean;
        final_level: string | null;
        skill_levels: Record<string, string> | null;
        override_reason: string | null;
        narrative: Narrative | null;
        narrative_approved_at: string | null;
        approved_at: string | null;
        approved_by: string | null;
        can_generate_pdf: boolean;
    };
    candidate: {
        name: string;
        email: string;
        age: number;
        is_minor: boolean;
    };
    attempt: { id: number; submitted_at: string | null };
    score: {
        section_scores: Record<string, number | null> | null;
        composite: string | null;
        suggested_level: string | null;
        suggested_level_label: string | null;
        variance_flagged: boolean;
    } | null;
    suggested_skill_levels: Record<string, string | null>;
    ai_recommendation: AiRecommendation | null;
    sections: {
        reading: {
            id: number;
            title: string | null;
            body: string | null;
            questions: Question[];
        }[];
        grammar_vocabulary: Question[];
        listening: {
            id: number;
            title: string | null;
            audio_url: string | null;
            questions: Question[];
        }[];
        writing: {
            prompt: string | null;
            essay: string | null;
            word_count: number | null;
        };
        speaking: {
            prompt: string | null;
            recording_url: string | null;
            duration_seconds: number | null;
            recording_attempts: number | null;
        };
    };
    objective_breakdown: Record<
        string,
        { correct: number; total: number; percentage: number }
    >;
    writing_guidelines: { titles: string[]; customized: boolean };
    speaking_guidelines: { titles: string[]; customized: boolean };
    ai_models: Record<string, { provider: string; model: string }>;
    ai_drafts: Record<string, AiDraft>;
    integrity_events: { type: string; label: string; occurred_at: string }[];
    notes: { id: number; author: string; note: string; created_at: string }[];
    result_links: {
        id: number;
        email_to: string;
        sent_at: string | null;
        sent_by: string | null;
        expires_at: string;
        last_viewed_at: string | null;
        expired: boolean;
    }[];
    levels: { value: string; label: string }[];
    staff: { id: number; name: string }[];
    supervises: boolean;
    errors: Record<string, string>;
    [key: string]: unknown;
}

function QuestionRow({
    question,
    index,
}: {
    question: Question;
    index: number;
}) {
    return (
        <div className="rounded-md border border-slate-100 p-2 text-sm">
            <p className="mb-1 font-medium text-slate-800">
                {index + 1}. {question.body}
            </p>
            <div className="grid gap-1 sm:grid-cols-2">
                {(question.options ?? []).map((option, optionIndex) => {
                    const isCorrect = optionIndex === question.correct_option;
                    const isSelected = optionIndex === question.selected;

                    return (
                        <div
                            key={optionIndex}
                            className={`rounded px-2 py-1 text-xs ${
                                isCorrect
                                    ? 'bg-emerald-50 text-emerald-800'
                                    : isSelected
                                      ? 'bg-red-50 text-red-700'
                                      : 'text-slate-600'
                            }`}
                        >
                            {String.fromCharCode(65 + optionIndex)}. {option}
                            {isCorrect && ' — correct answer'}
                            {isSelected && " — candidate's answer"}
                        </div>
                    );
                })}
            </div>
            {question.selected === null && (
                <p className="mt-1 text-xs text-slate-400">Not answered</p>
            )}
        </div>
    );
}

const AI_SUGGESTION_BADGES: Record<
    string,
    { label: string; tone: 'emerald' | 'red' | 'amber' }
> = {
    completed: { label: 'Ready', tone: 'emerald' },
    failed: { label: 'Unavailable', tone: 'red' },
    pending: { label: 'Preparing…', tone: 'amber' },
};

const CONFIDENCE_TONES: Record<string, 'emerald' | 'blue' | 'amber'> = {
    high: 'emerald',
    medium: 'blue',
    low: 'amber',
};

function DraftStatusBadge({ draft }: { draft?: AiDraft }) {
    const badge = draft ? AI_SUGGESTION_BADGES[draft.status] : undefined;

    return badge ? (
        <Badge tone={badge.tone}>{badge.label}</Badge>
    ) : (
        <Badge tone="slate">Not available yet</Badge>
    );
}

function DraftFailureNotice({ draft }: { draft?: AiDraft }) {
    if (draft?.status !== 'failed') {
        return null;
    }

    return (
        <div className="mb-2 rounded-md border border-amber-200 bg-amber-50 p-2">
            <p className="text-xs text-amber-800">
                This AI suggestion is unavailable — you can review this section
                normally without it.
            </p>
            {draft.error && (
                <details className="mt-1 text-xs text-slate-400">
                    <summary className="cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400">
                        Technical details
                    </summary>
                    <p className="mt-1 break-words">{draft.error}</p>
                </details>
            )}
        </div>
    );
}

function ObjectiveScoreTile({
    section,
    breakdown,
}: {
    section: string;
    breakdown?: { correct: number; total: number; percentage: number };
}) {
    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50/60 p-3">
            <div className="mb-1.5 flex items-center justify-between gap-2">
                <h3 className="text-sm font-semibold text-slate-800">
                    {SECTION_LABELS[section]}
                </h3>
                {breakdown ? (
                    <Badge tone="emerald">
                        {breakdown.correct}/{breakdown.total} correct
                    </Badge>
                ) : (
                    <Badge tone="slate">Not scored yet</Badge>
                )}
            </div>
            {breakdown && (
                <p className="text-lg font-semibold text-slate-900 tabular-nums">
                    {breakdown.percentage}%
                </p>
            )}
            <p className="mt-1 text-xs text-slate-500">
                Auto-scored from the question bank — supplied to the AI as
                context.
            </p>
        </div>
    );
}

function ProvisionalScoreCard({
    sectionLabel,
    skillName,
    draft,
    guidelines,
    model,
    showTranscript = false,
}: {
    sectionLabel: string;
    skillName: string;
    draft?: AiDraft;
    guidelines: { titles: string[]; customized: boolean };
    model?: { provider: string; model: string };
    showTranscript?: boolean;
}) {
    return (
        <div className="rounded-lg border border-sky-200 bg-gradient-to-b from-sky-50/70 to-white p-3">
            <div className="mb-2 flex items-center justify-between gap-2">
                <h3 className="flex items-center gap-1.5 text-sm font-semibold text-slate-800">
                    <span className="text-sky-500">
                        <SparkIcon />
                    </span>
                    {sectionLabel}
                </h3>
                <div className="flex items-center gap-1.5">
                    {draft?.confidence && (
                        <Badge
                            tone={CONFIDENCE_TONES[draft.confidence] ?? 'amber'}
                        >
                            {draft.confidence} confidence
                        </Badge>
                    )}
                    <DraftStatusBadge draft={draft} />
                </div>
            </div>
            <p className="mb-2 text-[11px] font-medium tracking-wide text-sky-700/80 uppercase">
                AI provisional score · staff-only
            </p>
            <DraftFailureNotice draft={draft} />
            {draft?.dimension_scores && (
                <dl className="mb-2 space-y-1.5">
                    {Object.entries(draft.dimension_scores).map(
                        ([dimension, value]) => {
                            const label = dimension.replace(/_/g, ' ');

                            return (
                                <div
                                    key={dimension}
                                    className="flex items-center justify-between gap-2"
                                >
                                    <dt className="text-xs text-slate-600 capitalize">
                                        {label}
                                    </dt>
                                    <dd className="flex items-center gap-2">
                                        <DimensionScale
                                            value={value}
                                            label={label}
                                        />
                                        <span className="w-7 text-right text-xs font-medium text-slate-700 tabular-nums">
                                            {value}/5
                                        </span>
                                    </dd>
                                </div>
                            );
                        },
                    )}
                </dl>
            )}
            {draft?.feedback && (
                <p className="mt-1 rounded-md bg-white/80 p-2 text-xs leading-relaxed text-slate-700">
                    {draft.feedback}
                </p>
            )}
            {showTranscript && draft?.transcript && (
                <div className="mt-2 text-xs">
                    <p className="font-medium text-slate-700">
                        Transcript (AI-generated) — always listen to the
                        recording too
                    </p>
                    <p className="mt-1 rounded-md bg-white/80 p-2 leading-relaxed whitespace-pre-wrap text-slate-600">
                        {draft.transcript}
                    </p>
                </div>
            )}
            <div className="mt-2 rounded-md bg-white/70 p-2">
                <p className="text-[11px] font-medium tracking-wide text-slate-500 uppercase">
                    Evaluated against the{' '}
                    {guidelines.customized ? 'school-configured' : 'default'}{' '}
                    {skillName} guidelines
                </p>
                <div className="mt-1 flex flex-wrap gap-1">
                    {guidelines.titles.map((title) => (
                        <Badge key={title} tone="blue">
                            {title}
                        </Badge>
                    ))}
                </div>
            </div>
            <p className="mt-2 text-[11px] text-slate-400">
                {draft?.generated_at && <>Generated {draft.generated_at}</>}
                {draft?.generated_at && model && ' · '}
                {model && (
                    <>
                        {model.provider} — {model.model}
                    </>
                )}
            </p>
        </div>
    );
}

function RecommendationCard({
    recommendation,
    levels,
    model,
}: {
    recommendation: AiRecommendation | null;
    levels: { value: string; label: string }[];
    model?: { provider: string; model: string };
}) {
    const levelLabel = (value: string | null | undefined) =>
        levels.find((level) => level.value === value)?.label ?? '—';

    return (
        <div className="mt-3 rounded-lg border border-sky-200 bg-gradient-to-b from-sky-50/70 to-white p-3">
            <div className="mb-2 flex items-center justify-between gap-2">
                <h3 className="flex items-center gap-1.5 text-sm font-semibold text-slate-800">
                    <span className="text-sky-500">
                        <SparkIcon />
                    </span>
                    AI recommended GLC level & skill-by-skill summary
                </h3>
                <div className="flex items-center gap-1.5">
                    {recommendation?.confidence && (
                        <Badge
                            tone={
                                CONFIDENCE_TONES[recommendation.confidence] ??
                                'amber'
                            }
                        >
                            {recommendation.confidence} confidence
                        </Badge>
                    )}
                    {recommendation ? (
                        <Badge
                            tone={
                                AI_SUGGESTION_BADGES[recommendation.status]
                                    ?.tone ?? 'amber'
                            }
                        >
                            {AI_SUGGESTION_BADGES[recommendation.status]
                                ?.label ?? recommendation.status}
                        </Badge>
                    ) : (
                        <Badge tone="slate">Not available yet</Badge>
                    )}
                </div>
            </div>
            <p className="mb-2 text-[11px] font-medium tracking-wide text-sky-700/80 uppercase">
                AI provisional recommendation · staff-only — you confirm or
                override every level below
            </p>
            {recommendation?.status === 'failed' && (
                <div className="mb-2 rounded-md border border-amber-200 bg-amber-50 p-2">
                    <p className="text-xs text-amber-800">
                        The AI recommendation is unavailable — the suggested
                        levels fall back to the automatic score bands.
                    </p>
                    {recommendation.error && (
                        <details className="mt-1 text-xs text-slate-400">
                            <summary className="cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400">
                                Technical details
                            </summary>
                            <p className="mt-1 break-words">
                                {recommendation.error}
                            </p>
                        </details>
                    )}
                </div>
            )}
            {recommendation?.status === 'completed' && (
                <>
                    <p className="mb-2 text-sm font-semibold text-slate-900">
                        Recommended overall level:{' '}
                        {recommendation.recommended_level_label ??
                            levelLabel(recommendation.recommended_level)}
                    </p>
                    {recommendation.rationale && (
                        <p className="mb-2 rounded-md bg-white/80 p-2 text-xs leading-relaxed text-slate-700">
                            {recommendation.rationale}
                        </p>
                    )}
                    <dl className="space-y-1.5">
                        {SECTION_ORDER.map((section) => (
                            <div
                                key={section}
                                className="rounded-md bg-white/70 p-2"
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <dt className="text-xs font-semibold text-slate-700">
                                        {SECTION_LABELS[section]}
                                    </dt>
                                    <dd>
                                        <Badge tone="blue">
                                            {levelLabel(
                                                recommendation.skill_levels?.[
                                                    section
                                                ],
                                            )}
                                        </Badge>
                                    </dd>
                                </div>
                                {recommendation.skill_summaries?.[section] && (
                                    <p className="mt-1 text-xs leading-relaxed text-slate-600">
                                        {
                                            recommendation.skill_summaries[
                                                section
                                            ]
                                        }
                                    </p>
                                )}
                            </div>
                        ))}
                    </dl>
                </>
            )}
            {(!recommendation || recommendation.status === 'pending') && (
                <p className="text-xs text-slate-500">
                    The AI recommendation is prepared after the Writing and
                    Speaking evaluations finish. You can review and confirm
                    levels without it.
                </p>
            )}
            <p className="mt-2 text-[11px] text-slate-400">
                {recommendation?.generated_at && (
                    <>Generated {recommendation.generated_at}</>
                )}
                {recommendation?.generated_at && model && ' · '}
                {model && (
                    <>
                        {model.provider} — {model.model}
                    </>
                )}
            </p>
        </div>
    );
}

function SaveState({ dirty, saved }: { dirty: boolean; saved: boolean }) {
    if (dirty) {
        return (
            <span className="text-xs font-medium text-amber-600" role="status">
                Unsaved changes
            </span>
        );
    }

    if (saved) {
        return (
            <span
                className="inline-flex items-center gap-1 text-xs font-medium text-emerald-700"
                role="status"
            >
                <CheckIcon className="h-3 w-3" />
                Saved
            </span>
        );
    }

    return null;
}

export default function ReviewShow() {
    const props = usePage<PageProps>().props;
    const {
        review,
        candidate,
        attempt,
        score,
        suggested_skill_levels,
        ai_recommendation,
        objective_breakdown = {},
        writing_guidelines = { titles: [], customized: false },
        speaking_guidelines = { titles: [], customized: false },
        ai_models = {},
        sections,
        ai_drafts,
        integrity_events,
        notes,
        result_links,
        levels,
        staff,
        supervises,
        errors,
    } = props;

    const baseUrl = `/staff/review/${review.id}`;
    const isSent = review.status === 'sent';

    const levelConfirmed = review.final_level !== null;
    const summaryApproved = review.narrative_approved_at !== null;
    const canGiveFinalApproval =
        review.status === 'in_review' &&
        levelConfirmed &&
        summaryApproved &&
        !isSent;

    const pipelineInput: PipelineInput = {
        status: review.status,
        submittedAt: attempt.submitted_at,
        hasScore: score !== null,
        aiScoringDone: ['writing', 'speaking'].every(
            (section) =>
                ai_drafts[section] && ai_drafts[section].status !== 'pending',
        ),
        aiSuggestionsUnavailable: Object.values(ai_drafts).some(
            (draft) => draft.status === 'failed',
        ),
        assigneeName: review.assignee,
        assignedToMe: review.is_assigned_to_me,
        levelConfirmed,
        summaryApprovedAt: review.narrative_approved_at,
        finalApprovalAt: review.approved_at,
        isMinor: candidate.is_minor,
        guardianConsentConfirmed: review.flags.includes(
            'guardian_consent_confirmed',
        ),
    };

    const suggestedFinalLevel =
        (ai_recommendation?.status === 'completed'
            ? ai_recommendation.recommended_level
            : null) ??
        score?.suggested_level ??
        null;
    const suggestedFinalLevelLabel =
        (ai_recommendation?.status === 'completed'
            ? ai_recommendation.recommended_level_label
            : null) ??
        score?.suggested_level_label ??
        null;

    const decisionForm = useForm({
        final_level: review.final_level ?? suggestedFinalLevel ?? '',
        skill_levels: Object.fromEntries(
            SECTION_ORDER.map((section) => [
                section,
                review.skill_levels?.[section] ??
                    suggested_skill_levels[section] ??
                    '',
            ]),
        ) as Record<string, string>,
        override_reason: review.override_reason ?? '',
    });

    const deviates =
        (suggestedFinalLevel &&
            decisionForm.data.final_level !== suggestedFinalLevel) ||
        SECTION_ORDER.some(
            (section) =>
                suggested_skill_levels[section] &&
                decisionForm.data.skill_levels[section] !==
                    suggested_skill_levels[section],
        );

    const narrativeForm = useForm({
        strengths: review.narrative?.strengths ?? '',
        areas_to_improve: review.narrative?.areas_to_improve ?? '',
        recommendation: review.narrative?.recommendation ?? '',
        next_steps: review.narrative?.next_steps ?? '',
    });

    const noteForm = useForm({ note: '' });
    const [assignTo, setAssignTo] = useState('');
    const [guardianConsent, setGuardianConsent] = useState(false);
    const [suggestionLoading, setSuggestionLoading] = useState(false);
    const [suggestionError, setSuggestionError] = useState<string | null>(null);

    const fetchSummarySuggestion = async () => {
        setSuggestionLoading(true);
        setSuggestionError(null);

        try {
            const response = await fetch(`${baseUrl}/narrative/draft`, {
                method: 'POST',
                headers: {
                    'X-XSRF-TOKEN': xsrfToken(),
                    Accept: 'application/json',
                },
            });
            const json = await response.json();

            if (!response.ok) {
                setSuggestionError(
                    json.message ??
                        'The AI suggestion is unavailable right now — you can write the summary yourself.',
                );
            } else {
                narrativeForm.setData({
                    strengths: json.narrative.strengths,
                    areas_to_improve: json.narrative.areas_to_improve,
                    recommendation: json.narrative.recommendation,
                    next_steps: json.narrative.next_steps,
                });
            }
        } catch {
            setSuggestionError(
                'The AI suggestion is unavailable right now — you can write the summary yourself.',
            );
        } finally {
            setSuggestionLoading(false);
        }
    };

    return (
        <GlcLayout title={`Placement test — ${candidate.name}`}>
            <Head title={`Placement test — ${candidate.name}`} />

            <div className="space-y-4">
                {/* Sticky candidate context header */}
                <div className="sticky top-14 z-30 -mx-4 border-b border-slate-200 bg-white/95 px-4 py-3 shadow-sm backdrop-blur sm:rounded-lg sm:border sm:shadow-sm">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="text-base font-semibold text-slate-900">
                                    {candidate.name}
                                </span>
                                <Badge
                                    tone={REVIEW_STATUS_TONES[review.status]}
                                >
                                    {REVIEW_STATUS_LABELS[review.status]}
                                </Badge>
                                {candidate.is_minor && (
                                    <Badge tone="amber">
                                        Under 18 (age {candidate.age})
                                    </Badge>
                                )}
                                {review.flags.includes('variance') && (
                                    <Badge tone="red">
                                        {ATTENTION_LABELS.variance}
                                    </Badge>
                                )}
                                {review.flags.includes('integrity') && (
                                    <Badge tone="red">
                                        {ATTENTION_LABELS.integrity}
                                    </Badge>
                                )}
                                {review.flags.includes(
                                    'guardian_consent_confirmed',
                                ) && (
                                    <Badge tone="emerald">
                                        Guardian consent confirmed
                                    </Badge>
                                )}
                            </div>
                            <p className="mt-1 text-xs text-slate-500">
                                {candidate.email} · age {candidate.age} ·
                                submitted {attempt.submitted_at ?? '—'} ·
                                reviewer: {review.assignee ?? 'no reviewer yet'}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            {!isSent && !review.is_assigned_to_me && (
                                <button
                                    type="button"
                                    className={btnSecondary}
                                    onClick={() =>
                                        router.post(
                                            `${baseUrl}/claim`,
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {review.assigned_to === null
                                        ? 'Start reviewing this test'
                                        : 'Assign to me'}
                                </button>
                            )}
                            {supervises && !isSent && (
                                <span className="flex items-center gap-1">
                                    <select
                                        className={inputCls}
                                        value={assignTo}
                                        aria-label="Hand over to another reviewer"
                                        onChange={(e) =>
                                            setAssignTo(e.target.value)
                                        }
                                    >
                                        <option value="">
                                            Hand over to another reviewer…
                                        </option>
                                        {staff.map((member) => (
                                            <option
                                                key={member.id}
                                                value={member.id}
                                            >
                                                {member.name}
                                            </option>
                                        ))}
                                    </select>
                                    <button
                                        type="button"
                                        className={btnSecondary}
                                        disabled={!assignTo}
                                        onClick={() =>
                                            router.post(
                                                `${baseUrl}/assign`,
                                                { user_id: Number(assignTo) },
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        Hand over
                                    </button>
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                <ProcessSteps input={pipelineInput} />

                {integrity_events.length > 0 && (
                    <Card title="Test-taking alerts">
                        <p className="mb-2 text-xs text-slate-500">
                            Recorded automatically while the candidate took the
                            test. Use your judgement during the review.
                        </p>
                        <ul className="space-y-1 text-sm text-slate-700">
                            {integrity_events.map((event, index) => (
                                <li key={index} className="flex gap-2">
                                    <Badge tone="red">
                                        {TEST_TAKING_EVENT_LABELS[event.type] ??
                                            event.label}
                                    </Badge>
                                    <span className="text-xs text-slate-500">
                                        {event.occurred_at}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </Card>
                )}

                <Card title="Automatic scores (staff-only)">
                    {score ? (
                        <div>
                            <table className="w-full text-sm">
                                <thead className="text-xs text-slate-500 uppercase">
                                    <tr>
                                        <th
                                            scope="col"
                                            className="py-1 text-left"
                                        >
                                            Section
                                        </th>
                                        <th
                                            scope="col"
                                            className="py-1 text-right"
                                        >
                                            Score
                                        </th>
                                        <th
                                            scope="col"
                                            className="py-1 text-right"
                                        >
                                            Suggested level
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {SECTION_ORDER.map((section) => {
                                        const value =
                                            score.section_scores?.[section];
                                        const suggested =
                                            suggested_skill_levels[section];
                                        const level = levels.find(
                                            (l) => l.value === suggested,
                                        );

                                        return (
                                            <tr
                                                key={section}
                                                className="border-t border-slate-100"
                                            >
                                                <td className="py-1.5">
                                                    {SECTION_LABELS[section]}
                                                </td>
                                                <td className="py-1.5 text-right">
                                                    {value != null ? (
                                                        <span className="inline-flex items-center justify-end gap-2">
                                                            <span
                                                                aria-hidden
                                                                className="hidden h-1.5 w-20 overflow-hidden rounded-full bg-slate-100 sm:block"
                                                            >
                                                                <span
                                                                    className={`block h-full rounded-full ${
                                                                        value >=
                                                                        70
                                                                            ? 'bg-emerald-500'
                                                                            : value >=
                                                                                40
                                                                              ? 'bg-amber-400'
                                                                              : 'bg-red-400'
                                                                    }`}
                                                                    style={{
                                                                        width: `${Math.min(100, Math.max(0, value))}%`,
                                                                    }}
                                                                />
                                                            </span>
                                                            <span className="font-medium tabular-nums">
                                                                {value}%
                                                            </span>
                                                        </span>
                                                    ) : (
                                                        <span className="text-slate-400">
                                                            {section ===
                                                            'speaking'
                                                                ? 'staff-assigned'
                                                                : 'not ready yet'}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-1.5 text-right">
                                                    {level?.label ?? '—'}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                            <div className="mt-2 flex items-center justify-between border-t border-slate-200 pt-2 text-sm">
                                <span className="font-semibold">
                                    Overall: {score.composite ?? '—'}% →{' '}
                                    {score.suggested_level_label ?? '—'}
                                </span>
                                {score.variance_flagged && (
                                    <Badge tone="red">
                                        {ATTENTION_LABELS.variance}
                                    </Badge>
                                )}
                            </div>
                        </div>
                    ) : (
                        <p className="text-sm text-slate-400">
                            The automatic checks have not finished yet.
                        </p>
                    )}
                </Card>

                <Card title="AI provisional scoring (staff-only — never shown to candidates or parents)">
                    <p className="mb-3 text-xs text-slate-500">
                        Reading, Grammar & Vocabulary and Listening are scored
                        from the question bank and supplied to the AI as
                        context. Writing and Speaking each get an AI
                        provisional score against the GLC guidelines (Speaking
                        is evaluated from the AI transcript — always listen to
                        the recording). The AI then recommends an overall GLC
                        level and a skill-by-skill summary for you to confirm
                        or override.
                    </p>
                    <div className="mb-3 grid gap-3 sm:grid-cols-3">
                        {(
                            [
                                'reading',
                                'grammar_vocabulary',
                                'listening',
                            ] as const
                        ).map((section) => (
                            <ObjectiveScoreTile
                                key={section}
                                section={section}
                                breakdown={objective_breakdown[section]}
                            />
                        ))}
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <ProvisionalScoreCard
                            sectionLabel={SECTION_LABELS.writing}
                            skillName="writing"
                            draft={ai_drafts.writing}
                            guidelines={writing_guidelines}
                            model={ai_models.writing}
                        />
                        <ProvisionalScoreCard
                            sectionLabel={SECTION_LABELS.speaking}
                            skillName="speaking"
                            draft={ai_drafts.speaking}
                            guidelines={speaking_guidelines}
                            model={ai_models.speaking_evaluation}
                            showTranscript
                        />
                    </div>
                    <RecommendationCard
                        recommendation={ai_recommendation}
                        levels={levels}
                        model={ai_models.writing}
                    />
                </Card>

                <Card title="Candidate answers">
                    <div className="space-y-3">
                        <details className="rounded-md border border-slate-200 p-3">
                            <summary className="cursor-pointer text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">
                                Reading
                            </summary>
                            {sections.reading.map((passage) => (
                                <div key={passage.id} className="mt-2">
                                    <p className="text-sm font-medium text-slate-800">
                                        {passage.title}
                                    </p>
                                    <p className="mb-2 text-xs whitespace-pre-wrap text-slate-500">
                                        {passage.body}
                                    </p>
                                    <div className="space-y-2">
                                        {passage.questions.map(
                                            (question, index) => (
                                                <QuestionRow
                                                    key={question.id}
                                                    question={question}
                                                    index={index}
                                                />
                                            ),
                                        )}
                                    </div>
                                </div>
                            ))}
                        </details>

                        <details className="rounded-md border border-slate-200 p-3">
                            <summary className="cursor-pointer text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">
                                Grammar & Vocabulary
                            </summary>
                            <div className="mt-2 space-y-2">
                                {sections.grammar_vocabulary.map(
                                    (question, index) => (
                                        <QuestionRow
                                            key={question.id}
                                            question={question}
                                            index={index}
                                        />
                                    ),
                                )}
                            </div>
                        </details>

                        <details className="rounded-md border border-slate-200 p-3">
                            <summary className="cursor-pointer text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">
                                Listening
                            </summary>
                            {sections.listening.map((clip) => (
                                <div key={clip.id} className="mt-2">
                                    <p className="text-sm font-medium text-slate-800">
                                        {clip.title}
                                    </p>
                                    {clip.audio_url && (
                                        <audio
                                            controls
                                            src={clip.audio_url}
                                            className="my-1 w-full"
                                        />
                                    )}
                                    <div className="space-y-2">
                                        {clip.questions.map(
                                            (question, index) => (
                                                <QuestionRow
                                                    key={question.id}
                                                    question={question}
                                                    index={index}
                                                />
                                            ),
                                        )}
                                    </div>
                                </div>
                            ))}
                        </details>

                        <details
                            className="rounded-md border border-slate-200 p-3"
                            open
                        >
                            <summary className="cursor-pointer text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">
                                Writing — essay
                                {sections.writing.word_count != null &&
                                    ` (${sections.writing.word_count} words)`}
                            </summary>
                            <p className="mt-1 text-xs text-slate-500">
                                Prompt: {sections.writing.prompt ?? '—'}
                            </p>
                            <p className="mt-2 rounded-md bg-slate-50 p-3 text-sm leading-relaxed whitespace-pre-wrap text-slate-800">
                                {sections.writing.essay ??
                                    'No essay submitted.'}
                            </p>
                        </details>

                        <details
                            className="rounded-md border border-slate-200 p-3"
                            open
                        >
                            <summary className="cursor-pointer text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400">
                                Speaking — recording
                                {sections.speaking.recording_attempts != null &&
                                    ` (attempt ${sections.speaking.recording_attempts})`}
                            </summary>
                            <p className="mt-1 text-xs text-slate-500">
                                Prompt: {sections.speaking.prompt ?? '—'}
                            </p>
                            {sections.speaking.recording_url ? (
                                <audio
                                    controls
                                    src={sections.speaking.recording_url}
                                    className="mt-2 w-full"
                                />
                            ) : (
                                <p className="mt-2 text-sm text-slate-400">
                                    No recording submitted.
                                </p>
                            )}
                        </details>
                    </div>
                </Card>

                <Card
                    title="Confirm the level"
                    aside={
                        <SaveState
                            dirty={decisionForm.isDirty}
                            saved={decisionForm.recentlySuccessful}
                        />
                    }
                >
                    <div className="grid gap-2 sm:grid-cols-3">
                        <Field
                            label={`Final GLC level (suggested: ${suggestedFinalLevelLabel ?? '—'})`}
                            error={errors.final_level}
                        >
                            <select
                                className={inputCls}
                                value={decisionForm.data.final_level}
                                disabled={isSent}
                                onChange={(e) =>
                                    decisionForm.setData(
                                        'final_level',
                                        e.target.value,
                                    )
                                }
                            >
                                <option value="">Select level…</option>
                                {levels.map((level) => (
                                    <option
                                        key={level.value}
                                        value={level.value}
                                    >
                                        {level.label}
                                    </option>
                                ))}
                            </select>
                        </Field>
                        {SECTION_ORDER.map((section) => (
                            <Field
                                key={section}
                                label={SECTION_LABELS[section]}
                                error={
                                    (errors as Record<string, string>)[
                                        `skill_levels.${section}`
                                    ]
                                }
                            >
                                <select
                                    className={inputCls}
                                    value={
                                        decisionForm.data.skill_levels[section]
                                    }
                                    disabled={isSent}
                                    onChange={(e) =>
                                        decisionForm.setData('skill_levels', {
                                            ...decisionForm.data.skill_levels,
                                            [section]: e.target.value,
                                        })
                                    }
                                >
                                    <option value="">Select level…</option>
                                    {levels.map((level) => (
                                        <option
                                            key={level.value}
                                            value={level.value}
                                        >
                                            {level.label}
                                        </option>
                                    ))}
                                </select>
                            </Field>
                        ))}
                    </div>

                    {deviates && (
                        <div className="mt-3">
                            <Field
                                label="Reason for the change (required — your levels differ from the automatic suggestion)"
                                error={errors.override_reason}
                            >
                                <textarea
                                    className={`${inputCls} min-h-20`}
                                    value={decisionForm.data.override_reason}
                                    disabled={isSent}
                                    onChange={(e) =>
                                        decisionForm.setData(
                                            'override_reason',
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                    )}
                    {!deviates && errors.override_reason && (
                        <p className="mt-2 text-xs text-red-600">
                            {errors.override_reason}
                        </p>
                    )}

                    <div className="mt-3 flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            className={btnPrimary}
                            disabled={decisionForm.processing || isSent}
                            onClick={() =>
                                decisionForm.put(`${baseUrl}/decision`, {
                                    preserveScroll: true,
                                })
                            }
                        >
                            {decisionForm.processing
                                ? 'Saving…'
                                : 'Save levels'}
                        </button>
                        {deviates && (
                            <span className="text-xs text-amber-600">
                                Your levels differ from the automatic
                                suggestion.
                            </span>
                        )}
                    </div>
                </Card>

                <Card
                    title="Parent summary (appears on the result PDF)"
                    aside={
                        <span className="flex items-center gap-2">
                            <SaveState
                                dirty={narrativeForm.isDirty}
                                saved={narrativeForm.recentlySuccessful}
                            />
                            {review.narrative_approved_at ? (
                                <Badge tone="emerald">
                                    Summary approved{' '}
                                    {review.narrative_approved_at}
                                </Badge>
                            ) : (
                                <Badge tone="amber">Not approved yet</Badge>
                            )}
                        </span>
                    }
                >
                    <div className="mb-2 flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            className={`${btnSecondary} gap-1.5`}
                            disabled={suggestionLoading || isSent}
                            onClick={() => void fetchSummarySuggestion()}
                        >
                            <span className="text-sky-500">
                                <SparkIcon />
                            </span>
                            {suggestionLoading
                                ? 'Preparing…'
                                : 'Get AI suggestion (staff-only)'}
                        </button>
                        <span className="text-xs text-slate-400">
                            The AI suggestion only fills in these fields for you
                            — review, edit and approve before anything reaches
                            parents.
                        </span>
                    </div>
                    {suggestionError && (
                        <p className="mb-2 text-xs text-red-600" role="alert">
                            {suggestionError}
                        </p>
                    )}

                    <div className="grid gap-2 sm:grid-cols-2">
                        {(
                            [
                                ['strengths', 'Strengths'],
                                ['areas_to_improve', 'Areas to improve'],
                                ['recommendation', 'Recommendation'],
                                ['next_steps', 'Next steps'],
                            ] as const
                        ).map(([key, label]) => (
                            <Field
                                key={key}
                                label={label}
                                error={(errors as Record<string, string>)[key]}
                            >
                                <textarea
                                    className={`${inputCls} min-h-24`}
                                    value={narrativeForm.data[key]}
                                    disabled={isSent}
                                    onChange={(e) =>
                                        narrativeForm.setData(
                                            key,
                                            e.target.value,
                                        )
                                    }
                                />
                            </Field>
                        ))}
                    </div>
                    {errors.narrative && (
                        <p className="mt-2 text-xs text-red-600">
                            {errors.narrative}
                        </p>
                    )}
                    <div className="mt-3 flex flex-wrap gap-2">
                        <button
                            type="button"
                            className={btnPrimary}
                            disabled={narrativeForm.processing || isSent}
                            onClick={() =>
                                narrativeForm.put(`${baseUrl}/narrative`, {
                                    preserveScroll: true,
                                })
                            }
                        >
                            {narrativeForm.processing
                                ? 'Saving…'
                                : 'Save summary'}
                        </button>
                        {!review.narrative_approved_at && !isSent && (
                            <button
                                type="button"
                                className={btnSecondary}
                                onClick={() =>
                                    router.post(
                                        `${baseUrl}/narrative/approve`,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Approve summary
                            </button>
                        )}
                    </div>
                </Card>

                <Card
                    title="Final approval"
                    aside={
                        review.approved_at ? (
                            <Badge tone="emerald">Approved</Badge>
                        ) : (
                            <Badge tone="amber">Not yet</Badge>
                        )
                    }
                >
                    <p className="text-sm text-slate-600">
                        Confirm you have reviewed the answers, confirmed levels,
                        and approved the parent summary. Preview and send unlock
                        after this step.
                    </p>
                    <ul className="mt-3 space-y-1 text-sm">
                        <li
                            className={`flex items-center gap-1.5 ${
                                levelConfirmed
                                    ? 'text-emerald-700'
                                    : 'text-slate-500'
                            }`}
                        >
                            {levelConfirmed ? (
                                <CheckIcon className="h-3.5 w-3.5" />
                            ) : (
                                <span
                                    aria-hidden
                                    className="inline-block h-3.5 w-3.5 rounded-full border-2 border-slate-300"
                                />
                            )}
                            Levels saved
                        </li>
                        <li
                            className={`flex items-center gap-1.5 ${
                                summaryApproved
                                    ? 'text-emerald-700'
                                    : 'text-slate-500'
                            }`}
                        >
                            {summaryApproved ? (
                                <CheckIcon className="h-3.5 w-3.5" />
                            ) : (
                                <span
                                    aria-hidden
                                    className="inline-block h-3.5 w-3.5 rounded-full border-2 border-slate-300"
                                />
                            )}
                            Parent summary approved
                        </li>
                    </ul>
                    {canGiveFinalApproval && (
                        <div className="mt-3">
                            <button
                                type="button"
                                className={btnPrimary}
                                onClick={() =>
                                    router.post(
                                        `${baseUrl}/approve`,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Give final approval
                            </button>
                        </div>
                    )}
                    {review.status === 'in_review' &&
                        !canGiveFinalApproval &&
                        !isSent && (
                            <p className="mt-3 text-sm text-slate-500">
                                Complete the checklist above before giving final
                                approval.
                            </p>
                        )}
                    {review.approved_at && (
                        <p className="mt-3 text-xs text-slate-500">
                            Final approval given {review.approved_at} by{' '}
                            {review.approved_by}
                        </p>
                    )}
                    {errors.status && (
                        <p className="mt-2 text-xs text-red-600">
                            {errors.status}
                        </p>
                    )}
                </Card>

                <Card title="Send the result">
                    {review.can_generate_pdf ? (
                        <div className="space-y-3">
                            <a
                                href={`${baseUrl}/pdf`}
                                target="_blank"
                                rel="noreferrer"
                                className={btnSecondary}
                            >
                                Preview PDF
                            </a>

                            <div className="rounded-md border border-slate-200 bg-slate-50 p-3">
                                <p className="mb-2 text-sm text-slate-700">
                                    Send the result link to{' '}
                                    <strong>{candidate.email}</strong> (valid 30
                                    days).
                                </p>
                                {candidate.is_minor && (
                                    <label className="mb-2 flex items-start gap-2 text-sm text-amber-800">
                                        <input
                                            type="checkbox"
                                            checked={guardianConsent}
                                            onChange={(e) =>
                                                setGuardianConsent(
                                                    e.target.checked,
                                                )
                                            }
                                            className="mt-0.5"
                                        />
                                        <span>
                                            Guardian consent received — I
                                            confirm GLC has guardian consent to
                                            send this candidate's result.
                                        </span>
                                    </label>
                                )}
                                {errors.guardian_consent && (
                                    <p className="mb-2 text-xs text-red-600">
                                        {errors.guardian_consent}
                                    </p>
                                )}
                                <button
                                    type="button"
                                    className={btnPrimary}
                                    onClick={() =>
                                        router.post(
                                            `${baseUrl}/send`,
                                            candidate.is_minor
                                                ? {
                                                      guardian_consent:
                                                          guardianConsent,
                                                  }
                                                : {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {isSent ? 'Resend result' : 'Send result'}
                                </button>
                            </div>
                        </div>
                    ) : (
                        <p className="text-sm text-slate-500">
                            {sendUnlockMessage({
                                status: review.status,
                                levelConfirmed,
                                summaryApproved,
                            })}
                        </p>
                    )}

                    {result_links.length > 0 && (
                        <div className="mt-3 border-t border-slate-200 pt-2">
                            <h3 className="mb-1 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                Sent results
                            </h3>
                            <ul className="space-y-1 text-xs text-slate-600">
                                {result_links.map((link) => (
                                    <li key={link.id}>
                                        Sent to {link.email_to} on{' '}
                                        {link.sent_at} by{' '}
                                        {link.sent_by ?? 'staff'} — expires{' '}
                                        {link.expires_at}
                                        {link.expired && ' (expired)'}
                                        {link.last_viewed_at &&
                                            ` — last viewed ${link.last_viewed_at}`}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </Card>

                <Card title="Internal notes (staff-only — never on the PDF)">
                    <div className="mb-3 space-y-2">
                        {notes.length === 0 && (
                            <p className="text-sm text-slate-400">
                                No notes yet.
                            </p>
                        )}
                        {notes.map((note) => (
                            <div
                                key={note.id}
                                className="rounded-md bg-slate-50 p-2 text-sm"
                            >
                                <p className="text-slate-700">{note.note}</p>
                                <p className="mt-1 text-xs text-slate-400">
                                    {note.author} · {note.created_at}
                                </p>
                            </div>
                        ))}
                    </div>
                    <div className="flex gap-2">
                        <input
                            className={inputCls}
                            placeholder="Add an internal note…"
                            aria-label="Add an internal note"
                            value={noteForm.data.note}
                            onChange={(e) =>
                                noteForm.setData('note', e.target.value)
                            }
                            onKeyDown={(e) => {
                                if (
                                    e.key === 'Enter' &&
                                    !noteForm.processing &&
                                    noteForm.data.note.trim() !== ''
                                ) {
                                    noteForm.post(`${baseUrl}/notes`, {
                                        preserveScroll: true,
                                        onSuccess: () => noteForm.reset(),
                                    });
                                }
                            }}
                        />
                        <button
                            type="button"
                            className={btnSecondary}
                            disabled={noteForm.processing}
                            onClick={() =>
                                noteForm.post(`${baseUrl}/notes`, {
                                    preserveScroll: true,
                                    onSuccess: () => noteForm.reset(),
                                })
                            }
                        >
                            Add note
                        </button>
                    </div>
                    {errors.note && (
                        <p className="mt-1 text-xs text-red-600">
                            {errors.note}
                        </p>
                    )}
                </Card>
            </div>
        </GlcLayout>
    );
}
