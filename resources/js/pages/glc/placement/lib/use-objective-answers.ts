import { useState } from 'react';
import { placementApi } from './placement-api';
import {
    type ObjectiveAnswers,
    type ObjectiveAnswerValue,
    type SaveState,
} from './types';

/**
 * Optimistic local answer state with immediate server persistence.
 * Values are option indexes (MCQ) or typed text (gap fill). Failed
 * saves roll the indicator to "error"; the upsert endpoint is
 * idempotent so retrying on the next save is safe.
 */
export function useObjectiveAnswers(initial: ObjectiveAnswers) {
    const [answers, setAnswers] = useState<ObjectiveAnswers>(initial);
    const [saveState, setSaveState] = useState<SaveState>('idle');

    const select = (itemId: number, value: ObjectiveAnswerValue) => {
        setAnswers((current) => ({ ...current, [itemId]: value }));
        setSaveState('saving');

        void placementApi.saveAnswer(itemId, value).then((result) => {
            setSaveState(result.ok ? 'saved' : 'error');
        });
    };

    return { answers, select, saveState };
}
