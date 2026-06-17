import { useEffect, useRef, useState } from 'react';
import {
    isAnsweredValue,
    type McqQuestion,
    type ObjectiveAnswers,
    type ObjectiveAnswerValue,
} from '../lib/types';

interface McqListProps {
    questions: McqQuestion[];
    answers: ObjectiveAnswers;
    onSelect: (itemId: number, value: ObjectiveAnswerValue) => void;
    startNumber?: number;
    disabled?: boolean;
    highlightUnanswered?: boolean;
}

export function questionAnchorId(questionId: number): string {
    return `placement-question-${questionId}`;
}

/**
 * Objective question list with immediate auto-save. MCQ rows persist
 * the moment an option is chosen; gap-fill rows persist on blur,
 * Enter, and shortly after typing pauses. When the candidate tries to
 * finish with gaps, unanswered questions are highlighted; the highlight
 * clears per question as soon as it is answered.
 */
export default function McqList({
    questions,
    answers,
    onSelect,
    startNumber = 1,
    disabled = false,
    highlightUnanswered = false,
}: McqListProps) {
    return (
        <ol className="space-y-4">
            {questions.map((question, index) => {
                const saved = answers[question.id];
                const highlighted =
                    highlightUnanswered && !isAnsweredValue(saved);

                return (
                    <li
                        key={question.id}
                        id={questionAnchorId(question.id)}
                        className={`rounded-lg border bg-card p-4 transition-colors ${
                            highlighted
                                ? 'border-amber-400 ring-2 ring-amber-300'
                                : 'border-border'
                        }`}
                    >
                        <p className="text-sm font-medium">
                            <span className="mr-1.5 text-muted-foreground">
                                {startNumber + index}.
                            </span>
                            {question.body}
                            {highlighted && (
                                <span className="ml-2 inline-block rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                    Not answered yet
                                </span>
                            )}
                        </p>
                        {question.format === 'gap_fill' ? (
                            <GapFillInput
                                questionId={question.id}
                                savedText={
                                    typeof saved === 'string' ? saved : ''
                                }
                                onSave={onSelect}
                                disabled={disabled}
                            />
                        ) : (
                            <McqOptions
                                question={question}
                                selected={saved}
                                onSelect={onSelect}
                                disabled={disabled}
                            />
                        )}
                    </li>
                );
            })}
        </ol>
    );
}

function McqOptions({
    question,
    selected,
    onSelect,
    disabled,
}: {
    question: McqQuestion;
    selected: ObjectiveAnswerValue | undefined;
    onSelect: (itemId: number, value: number) => void;
    disabled: boolean;
}) {
    return (
        <div className="mt-3 space-y-2">
            {question.options.map((option, optionIndex) => {
                const isSelected = selected === optionIndex;

                return (
                    <label
                        key={optionIndex}
                        className={`flex cursor-pointer items-start gap-2.5 rounded-lg border px-3 py-2 text-sm transition-colors ${
                            isSelected
                                ? 'border-primary bg-primary/5'
                                : 'border-border hover:bg-accent'
                        } ${disabled ? 'cursor-not-allowed opacity-60' : ''}`}
                    >
                        <input
                            type="radio"
                            name={`question-${question.id}`}
                            checked={isSelected}
                            disabled={disabled}
                            onChange={() => onSelect(question.id, optionIndex)}
                            className="mt-0.5 border-input text-primary"
                        />
                        <span>{option}</span>
                    </label>
                );
            })}
        </div>
    );
}

const GAP_FILL_DEBOUNCE_MS = 800;

/**
 * Free-text answer for gap-fill (sentence completion) questions. Only
 * non-empty, changed text is sent; clearing the field keeps the last
 * saved answer on the server, so "answered" still reflects saved state.
 */
function GapFillInput({
    questionId,
    savedText,
    onSave,
    disabled,
}: {
    questionId: number;
    savedText: string;
    onSave: (itemId: number, value: string) => void;
    disabled: boolean;
}) {
    const [draft, setDraft] = useState(savedText);
    const lastSavedRef = useRef(savedText);
    const debounceRef = useRef<number | null>(null);

    const clearDebounce = () => {
        if (debounceRef.current !== null) {
            window.clearTimeout(debounceRef.current);
            debounceRef.current = null;
        }
    };

    useEffect(() => clearDebounce, []);

    const save = (value: string) => {
        const trimmed = value.trim();

        if (trimmed === '' || trimmed === lastSavedRef.current) {
            return;
        }

        lastSavedRef.current = trimmed;
        onSave(questionId, trimmed);
    };

    return (
        <input
            type="text"
            value={draft}
            maxLength={200}
            disabled={disabled}
            placeholder="Type your answer"
            aria-label="Your answer"
            autoComplete="off"
            onChange={(event) => {
                const value = event.target.value;
                setDraft(value);
                clearDebounce();
                debounceRef.current = window.setTimeout(
                    () => save(value),
                    GAP_FILL_DEBOUNCE_MS,
                );
            }}
            onBlur={() => {
                clearDebounce();
                save(draft);
            }}
            onKeyDown={(event) => {
                if (event.key === 'Enter') {
                    clearDebounce();
                    save(draft);
                }
            }}
            className="mt-3 w-full rounded-lg border border-border bg-background px-3 py-2 text-sm transition-colors focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
        />
    );
}
