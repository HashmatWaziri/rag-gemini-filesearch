import { type McqQuestion } from '../lib/types';

interface McqListProps {
    questions: McqQuestion[];
    answers: Record<number, number>;
    onSelect: (itemId: number, selected: number) => void;
    startNumber?: number;
    disabled?: boolean;
    highlightUnanswered?: boolean;
}

export function questionAnchorId(questionId: number): string {
    return `placement-question-${questionId}`;
}

/**
 * MCQ list with immediate auto-save on selection (objective answers
 * persist the moment they are chosen). When the candidate tries to
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
                const isUnanswered = answers[question.id] === undefined;
                const highlighted = highlightUnanswered && isUnanswered;

                return (
                    <li
                        key={question.id}
                        id={questionAnchorId(question.id)}
                        className={`rounded-lg border bg-white p-4 transition-colors ${
                            highlighted
                                ? 'border-amber-400 ring-2 ring-amber-300'
                                : 'border-slate-200'
                        }`}
                    >
                        <p className="text-sm font-medium">
                            <span className="mr-1.5 text-slate-400">
                                {startNumber + index}.
                            </span>
                            {question.body}
                            {highlighted && (
                                <span className="ml-2 inline-block rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                    Not answered yet
                                </span>
                            )}
                        </p>
                        <div className="mt-3 space-y-2">
                            {question.options.map((option, optionIndex) => {
                                const selected =
                                    answers[question.id] === optionIndex;

                                return (
                                    <label
                                        key={optionIndex}
                                        className={`flex cursor-pointer items-start gap-2.5 rounded-lg border px-3 py-2 text-sm transition-colors ${
                                            selected
                                                ? 'border-emerald-500 bg-emerald-50'
                                                : 'border-slate-200 hover:bg-slate-50'
                                        } ${disabled ? 'cursor-not-allowed opacity-60' : ''}`}
                                    >
                                        <input
                                            type="radio"
                                            name={`question-${question.id}`}
                                            checked={selected}
                                            disabled={disabled}
                                            onChange={() =>
                                                onSelect(
                                                    question.id,
                                                    optionIndex,
                                                )
                                            }
                                            className="mt-0.5 border-slate-300 text-emerald-600"
                                        />
                                        <span>{option}</span>
                                    </label>
                                );
                            })}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
