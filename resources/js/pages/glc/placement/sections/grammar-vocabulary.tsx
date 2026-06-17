import FinishSection from '../components/finish-section';
import McqList from '../components/mcq-list';
import SectionShell from '../components/section-shell';
import {
    type McqQuestion,
    type ObjectiveAnswers,
    type SectionPageProps,
} from '../lib/types';
import { useObjectiveAnswers } from '../lib/use-objective-answers';
import { useUnansweredReview } from '../lib/use-unanswered-review';

interface GrammarVocabularyProps extends SectionPageProps {
    questions: McqQuestion[];
    answers: ObjectiveAnswers;
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
            <p className="mb-4 text-sm text-muted-foreground">
                Choose the best option for each item. Your answers are saved
                automatically.
            </p>

            <McqList
                questions={questions}
                answers={answers}
                onSelect={select}
                highlightUnanswered={highlightUnanswered}
            />

            <div className="mt-6 rounded-lg border border-primary/20 bg-primary/5 p-3 text-sm text-primary">
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
