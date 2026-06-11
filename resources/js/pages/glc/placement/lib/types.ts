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
}

export interface SectionPageProps {
    candidateName: string;
    progress: SectionProgress;
    timer: SectionTimer;
    config: SectionConfig;
}

export type SaveState = 'idle' | 'saving' | 'saved' | 'error';
