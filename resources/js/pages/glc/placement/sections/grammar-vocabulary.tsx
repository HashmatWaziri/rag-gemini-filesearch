import FinishSection from '../components/finish-section';
import McqList from '../components/mcq-list';
import SectionShell from '../components/section-shell';
import { type McqQuestion, type SectionPageProps } from '../lib/types';
import { useObjectiveAnswers } from '../lib/use-objective-answers';
import { useUnansweredReview } from '../lib/use-unanswered-review';

interface GrammarVocabularyProps extends SectionPageProps {
    questions: McqQuestion[];
    answers: Record<number, number>;
}

export default function GrammarVocabulary({
    progress,
    timer,
    config,
    questions,
    answers: initialAnswers,
}: GrammarVocabularyProps) {
    const { answers, select, saveState } = useObjectiveAnswers(initialAnswers);
    const { highlightUnanswered, unansweredCount, reviewUnanswered } =
        useUnansweredReview(questions, answers);

    return (
        <SectionShell
            progress={progress}
            timer={timer}
            config={config}
            saveState={saveState}
        >
            <p className="mb-4 text-sm text-slate-600">
                Choose the best option for each item. Your answers are saved
                automatically.
            </p>

            <McqList
                questions={questions}
                answers={answers}
                onSelect={select}
                highlightUnanswered={highlightUnanswered}
            />

            <div className="mt-6 rounded-lg border border-sky-200 bg-sky-50 p-3 text-sm text-sky-800">
                <p className="font-semibold">Next up: Listening</p>
                <p>
                    The first clip starts playing automatically about{' '}
                    {config.listeningAutoStartSeconds} seconds after the
                    Listening section opens, and it plays only once. Make sure
                    your sound is on and you are ready to listen before you
                    finish this section.
                </p>
            </div>

            <FinishSection
                section={progress.current}
                nextLabel="Listening"
                unansweredCount={unansweredCount}
                onReviewUnanswered={reviewUnanswered}
            />
        </SectionShell>
    );
}
