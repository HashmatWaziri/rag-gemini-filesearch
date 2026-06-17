export interface SectionProgress {
    current: string;
    currentLabel: string;
    currentIndex: number;
    total: number;
    sections: {
        value: string;
        label: string;
        order: number;
        status: 'locked' | 'in_progress' | 'completed';
    }[];
}

export interface SectionTimer {
    remainingSeconds: number;
    timeLimitSeconds: number;
}

export interface SectionConfig {
    heartbeatIntervalSeconds: number;
    autosaveIntervalSeconds: number;
    listeningAutoStartSeconds: number;
}

export interface McqQuestion {
    id: number;
    position: number;
    body: string | null;
    options: string[];
    format: 'mcq' | 'gap_fill';
}

/**
 * Saved objective answer: option index for MCQ, typed text for gap fill.
 */
export type ObjectiveAnswerValue = number | string;

export type ObjectiveAnswers = Record<number, ObjectiveAnswerValue>;

/**
 * A question counts as answered once it has a saved option index (MCQ)
 * or a non-empty saved text (gap fill).
 */
export function isAnsweredValue(
    value: ObjectiveAnswerValue | undefined,
): boolean {
    return (
        typeof value === 'number' ||
        (typeof value === 'string' && value.trim() !== '')
    );
}

export interface SectionPageProps {
    candidateName: string;
    progress: SectionProgress;
    timer: SectionTimer;
    config: SectionConfig;
}

export type SaveState = 'idle' | 'saving' | 'saved' | 'error';
