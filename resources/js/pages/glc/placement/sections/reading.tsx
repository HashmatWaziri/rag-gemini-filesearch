import FinishSection from '../components/finish-section';
import McqList from '../components/mcq-list';
import SectionShell from '../components/section-shell';
import { type McqQuestion, type SectionPageProps } from '../lib/types';
import { useObjectiveAnswers } from '../lib/use-objective-answers';
import { useUnansweredReview } from '../lib/use-unanswered-review';

interface Passage {
    id: number;
    position: number;
    title: string | null;
    body: string | null;
    questions: McqQuestion[];
}

interface ReadingProps extends SectionPageProps {
    passages: Passage[];
    answers: Record<number, number>;
}

export default function Reading({
    progress,
    timer,
    config,
    passages,
    answers: initialAnswers,
}: ReadingProps) {
    const { answers, select, saveState } = useObjectiveAnswers(initialAnswers);

    const allQuestions = passages.flatMap((passage) => passage.questions);
    const { highlightUnanswered, unansweredCount, reviewUnanswered } =
        useUnansweredReview(allQuestions, answers);

    let questionNumber = 1;

    return (
        <SectionShell
            progress={progress}
            timer={timer}
            config={config}
            saveState={saveState}
        >
            <div className="space-y-6">
                {passages.map((passage) => {
                    const startNumber = questionNumber;
                    questionNumber += passage.questions.length;

                    return (
                        <section key={passage.id}>
                            <article className="rounded-lg border border-slate-200 bg-white p-4">
                                {passage.title && (
                                    <h2 className="mb-2 font-semibold">
                                        {passage.title}
                                    </h2>
                                )}
                                <div className="text-sm leading-relaxed whitespace-pre-line text-slate-700">
                                    {passage.body}
                                </div>
                            </article>
                            <div className="mt-3">
                                <McqList
                                    questions={passage.questions}
                                    answers={answers}
                                    onSelect={select}
                                    startNumber={startNumber}
                                    highlightUnanswered={highlightUnanswered}
                                />
                            </div>
                        </section>
                    );
                })}
            </div>

            <FinishSection
                section={progress.current}
                nextLabel="Grammar & Vocabulary"
                unansweredCount={unansweredCount}
                onReviewUnanswered={reviewUnanswered}
            />
        </SectionShell>
    );
}
