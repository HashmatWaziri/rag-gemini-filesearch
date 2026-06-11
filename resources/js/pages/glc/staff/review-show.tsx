import GlcLayout from '@/layouts/glc-layout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    Badge,
    btnPrimary,
    btnSecondary,
    Card,
    Field,
    inputCls,
    SECTION_LABELS,
    SECTION_ORDER,
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

interface PageProps {
    review: {
        id: number;
        status: string;
        status_label: string;
        flags: string[];
        assigned_to: number | null;
        assignee: string | null;
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

function QuestionRow({ question, index }: { question: Question; index: number }) {
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
                            {isCorrect && ' ✓'}
                            {isSelected && !isCorrect && ' (candidate)'}
                            {isSelected && isCorrect && ' (candidate)'}
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

function DraftCard({ section, draft }: { section: string; draft?: AiDraft }) {
    return (
        <div className="rounded-md border border-slate-200 p-3">
            <div className="mb-2 flex items-center justify-between">
                <h3 className="text-sm font-semibold text-slate-700">
                    {SECTION_LABELS[section]}
                </h3>
                {draft ? (
                    <Badge
                        tone={
                            draft.status === 'completed'
                                ? 'emerald'
                                : draft.status === 'failed'
                                  ? 'red'
                                  : 'amber'
                        }
                    >
                        {draft.status}
                    </Badge>
                ) : (
                    <Badge tone="slate">not generated</Badge>
                )}
            </div>
            {draft?.status === 'failed' && (
                <p className="text-xs text-red-600">
                    {draft.error ?? 'Draft generation failed.'} Review manually.
                </p>
            )}
            {draft?.dimension_scores && (
                <table className="mb-2 w-full text-xs">
                    <tbody>
                        {Object.entries(draft.dimension_scores).map(
                            ([dimension, value]) => (
                                <tr
                                    key={dimension}
                                    className="border-b border-slate-100"
                                >
                                    <td className="py-1 text-slate-600 capitalize">
                                        {dimension.replace('_', ' ')}
                                    </td>
                                    <td className="py-1 text-right font-medium">
                                        {value}/5
                                    </td>
                                </tr>
                            ),
                        )}
                    </tbody>
                </table>
            )}
            {draft?.confidence && (
                <p className="text-xs text-slate-500">
                    Confidence:{' '}
                    <span className="font-medium">{draft.confidence}</span>
                </p>
            )}
            {draft?.feedback && (
                <p className="mt-1 text-xs leading-relaxed text-slate-600">
                    {draft.feedback}
                </p>
            )}
            {draft?.transcript && (
                <details className="mt-2 text-xs">
                    <summary className="cursor-pointer font-medium text-slate-600">
                        Transcript
                    </summary>
                    <p className="mt-1 leading-relaxed whitespace-pre-wrap text-slate-600">
                        {draft.transcript}
                    </p>
                </details>
            )}
        </div>
    );
}

export default function ReviewShow() {
    const props = usePage<PageProps>().props;
    const {
        review,
        candidate,
        attempt,
        score,
        suggested_skill_levels,
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

    const decisionForm = useForm({
        final_level: review.final_level ?? score?.suggested_level ?? '',
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
        (score?.suggested_level &&
            decisionForm.data.final_level !== score.suggested_level) ||
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
    const [draftLoading, setDraftLoading] = useState(false);
    const [draftError, setDraftError] = useState<string | null>(null);

    const generateNarrativeDraft = async () => {
        setDraftLoading(true);
        setDraftError(null);

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
                setDraftError(
                    json.message ?? 'The AI draft could not be generated.',
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
            setDraftError('The AI draft could not be generated.');
        } finally {
            setDraftLoading(false);
        }
    };

    return (
        <GlcLayout title={`Review — ${candidate.name}`}>
            <Head title={`Review ${candidate.name}`} />

            <div className="space-y-4">
                <Card>
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="text-base font-semibold text-slate-900">
                                    {candidate.name}
                                </span>
                                <Badge tone="blue">{review.status_label}</Badge>
                                {candidate.is_minor && (
                                    <Badge tone="amber">
                                        Minor (age {candidate.age})
                                    </Badge>
                                )}
                                {review.flags.includes('variance') && (
                                    <Badge tone="red">Variance flag</Badge>
                                )}
                                {review.flags.includes('integrity') && (
                                    <Badge tone="red">Integrity flag</Badge>
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
                                assignee: {review.assignee ?? 'unassigned'}
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            {!isSent && (
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
                                    Claim
                                </button>
                            )}
                            {supervises && !isSent && (
                                <span className="flex items-center gap-1">
                                    <select
                                        className={inputCls}
                                        value={assignTo}
                                        onChange={(e) =>
                                            setAssignTo(e.target.value)
                                        }
                                    >
                                        <option value="">Assign to…</option>
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
                                        Assign
                                    </button>
                                </span>
                            )}
                        </div>
                    </div>
                </Card>

                {integrity_events.length > 0 && (
                    <Card title="Integrity events">
                        <ul className="space-y-1 text-sm text-slate-700">
                            {integrity_events.map((event, index) => (
                                <li key={index} className="flex gap-2">
                                    <Badge tone="red">{event.label}</Badge>
                                    <span className="text-xs text-slate-500">
                                        {event.occurred_at}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </Card>
                )}

                <Card title="Objective scores (staff-only)">
                    {score ? (
                        <div>
                            <table className="w-full text-sm">
                                <thead className="text-xs text-slate-500 uppercase">
                                    <tr>
                                        <th className="py-1 text-left">
                                            Section
                                        </th>
                                        <th className="py-1 text-right">
                                            Score
                                        </th>
                                        <th className="py-1 text-right">
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
                                                <td className="py-1.5 text-right font-medium">
                                                    {value != null
                                                        ? `${value}%`
                                                        : 'pending'}
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
                                    Composite: {score.composite ?? '—'}% →{' '}
                                    {score.suggested_level_label ?? '—'}
                                </span>
                                {score.variance_flagged && (
                                    <Badge tone="red">
                                        High cross-section variance
                                    </Badge>
                                )}
                            </div>
                        </div>
                    ) : (
                        <p className="text-sm text-slate-400">
                            Not scored yet.
                        </p>
                    )}
                </Card>

                <Card title="AI provisional drafts (staff-only — never shown to candidates)">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <DraftCard
                            section="writing"
                            draft={ai_drafts.writing}
                        />
                        <DraftCard
                            section="speaking"
                            draft={ai_drafts.speaking}
                        />
                    </div>
                </Card>

                <Card title="Candidate answers">
                    <div className="space-y-3">
                        <details className="rounded-md border border-slate-200 p-3">
                            <summary className="cursor-pointer text-sm font-semibold">
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
                            <summary className="cursor-pointer text-sm font-semibold">
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
                            <summary className="cursor-pointer text-sm font-semibold">
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
                            <summary className="cursor-pointer text-sm font-semibold">
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
                            <summary className="cursor-pointer text-sm font-semibold">
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

                <Card title="Decision — confirm or override">
                    <div className="grid gap-2 sm:grid-cols-3">
                        <Field
                            label={`Final GLC level (suggested: ${score?.suggested_level_label ?? '—'})`}
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
                                label="Override reason (required — you are deviating from the suggested values)"
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
                            Save decision
                        </button>
                        {review.status === 'in_review' && (
                            <button
                                type="button"
                                className={btnSecondary}
                                onClick={() =>
                                    router.post(
                                        `${baseUrl}/approve`,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Approve review
                            </button>
                        )}
                        {review.approved_at && (
                            <span className="text-xs text-slate-500">
                                Approved {review.approved_at} by{' '}
                                {review.approved_by}
                            </span>
                        )}
                        {errors.status && (
                            <span className="text-xs text-red-600">
                                {errors.status}
                            </span>
                        )}
                    </div>
                </Card>

                <Card
                    title="Reviewer narrative (appears on the PDF)"
                    aside={
                        review.narrative_approved_at ? (
                            <Badge tone="emerald">
                                Narrative approved{' '}
                                {review.narrative_approved_at}
                            </Badge>
                        ) : (
                            <Badge tone="amber">Not approved yet</Badge>
                        )
                    }
                >
                    <div className="mb-2 flex items-center gap-2">
                        <button
                            type="button"
                            className={btnSecondary}
                            disabled={draftLoading || isSent}
                            onClick={() => void generateNarrativeDraft()}
                        >
                            {draftLoading
                                ? 'Generating…'
                                : 'Generate AI draft (staff-only helper)'}
                        </button>
                        <span className="text-xs text-slate-400">
                            Drafts only prefill the editor — review, edit and
                            approve before the PDF.
                        </span>
                    </div>
                    {draftError && (
                        <p className="mb-2 text-xs text-red-600">
                            {draftError}
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
                                error={
                                    (errors as Record<string, string>)[key]
                                }
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
                            Save narrative
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
                                Approve narrative
                            </button>
                        )}
                    </div>
                </Card>

                <Card title="Result delivery">
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
                                    <strong>{candidate.email}</strong> (valid
                                    30 days).
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
                                            send this minor candidate's result.
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
                            The PDF and send action unlock once the review is
                            approved and the narrative is approved.
                        </p>
                    )}

                    {result_links.length > 0 && (
                        <div className="mt-3 border-t border-slate-200 pt-2">
                            <h3 className="mb-1 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                Sent links
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
                            value={noteForm.data.note}
                            onChange={(e) =>
                                noteForm.setData('note', e.target.value)
                            }
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
