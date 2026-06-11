import { useState } from 'react';
import { placementApi } from './placement-api';
import { type SaveState } from './types';

/**
 * Optimistic local answer state with immediate server persistence.
 * Failed saves roll the indicator to "error"; the upsert endpoint is
 * idempotent so retrying on the next selection is safe.
 */
export function useObjectiveAnswers(initial: Record<number, number>) {
    const [answers, setAnswers] = useState<Record<number, number>>(initial);
    const [saveState, setSaveState] = useState<SaveState>('idle');

    const select = (itemId: number, selected: number) => {
        setAnswers((current) => ({ ...current, [itemId]: selected }));
        setSaveState('saving');

        void placementApi.saveAnswer(itemId, selected).then((result) => {
            setSaveState(result.ok ? 'saved' : 'error');
        });
    };

    return { answers, select, saveState };
}
