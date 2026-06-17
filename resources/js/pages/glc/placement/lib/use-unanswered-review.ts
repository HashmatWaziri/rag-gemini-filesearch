import { useState } from 'react';
import { questionAnchorId } from '../components/mcq-list';
import {
    isAnsweredValue,
    type McqQuestion,
    type ObjectiveAnswers,
} from './types';

/**
 * Drives the "first Finish press reviews the gaps" flow: highlights all
 * unanswered questions and scrolls to the first one. Per-question
 * highlighting clears automatically as answers arrive because it is
 * derived from the current answers map.
 */
export function useUnansweredReview(
    questions: McqQuestion[],
    answers: ObjectiveAnswers,
) {
    const [highlightUnanswered, setHighlightUnanswered] = useState(false);

    const unansweredIds = questions
        .filter((question) => !isAnsweredValue(answers[question.id]))
        .map((question) => question.id);

    const reviewUnanswered = () => {
        setHighlightUnanswered(true);

        const first = unansweredIds[0];

        if (first === undefined) {
            return;
        }

        document
            .getElementById(questionAnchorId(first))
            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    return {
        highlightUnanswered,
        unansweredCount: unansweredIds.length,
        reviewUnanswered,
    };
}
