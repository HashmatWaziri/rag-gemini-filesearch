import { router } from '@inertiajs/react';
import { type ReactNode, useEffect, useRef, useState } from 'react';
import { placementApi } from '../lib/placement-api';
import {
    type SaveState,
    type SectionConfig,
    type SectionProgress,
    type SectionTimer,
} from '../lib/types';
import CandidateShell from './candidate-shell';

interface SectionShellProps {
    progress: SectionProgress;
    timer: SectionTimer;
    config: SectionConfig;
    saveState?: SaveState;
    children: ReactNode;
}

function formatClock(totalSeconds: number): string {
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

const SAVE_LABEL: Record<SaveState, string> = {
    idle: '',
    saving: 'Saving...',
    saved: 'All changes saved',
    error: 'Save failed - retrying',
};

/**
 * Chrome for an active test section: progress indicator (Section x of 5),
 * visible countdown timer synced to the server via heartbeats, autosave
 * indicator, and tab-switch detection that warns the candidate and records
 * an integrity event.
 */
export default function SectionShell({
    progress,
    timer,
    config,
    saveState = 'idle',
    children,
}: SectionShellProps) {
    const [remaining, setRemaining] = useState(timer.remainingSeconds);
    const [tabWarning, setTabWarning] = useState(false);
    const remainingRef = useRef(timer.remainingSeconds);

    // Local 1s countdown for display; the server stays authoritative.
    useEffect(() => {
        const tick = window.setInterval(() => {
            remainingRef.current = Math.max(0, remainingRef.current - 1);
            setRemaining(remainingRef.current);
        }, 1000);

        return () => window.clearInterval(tick);
    }, []);

    // Server heartbeat: accumulates time server-side, syncs the clock, and
    // follows the server when the section is force-completed.
    useEffect(() => {
        const sendHeartbeat = async () => {
            const result = await placementApi.heartbeat();

            if (result.data.redirect) {
                router.visit(result.data.redirect);
                return;
            }

            if (typeof result.data.remainingSeconds === 'number') {
                remainingRef.current = result.data.remainingSeconds;
                setRemaining(result.data.remainingSeconds);
            }
        };

        const interval = window.setInterval(
            () => void sendHeartbeat(),
            config.heartbeatIntervalSeconds * 1000,
        );

        return () => window.clearInterval(interval);
    }, [config.heartbeatIntervalSeconds]);

    // When the local clock runs out, ask the server to confirm and advance.
    useEffect(() => {
        if (remaining > 0) {
            return;
        }

        void placementApi.heartbeat().then((result) => {
            if (result.data.redirect) {
                router.visit(result.data.redirect);
            }
        });
    }, [remaining]);

    // Tab-switch integrity signal: warn the candidate and record the event.
    useEffect(() => {
        const onVisibilityChange = () => {
            if (document.visibilityState === 'hidden') {
                void placementApi.reportIntegrity('tab_switch');
                setTabWarning(true);
            }
        };

        document.addEventListener('visibilitychange', onVisibilityChange);

        return () =>
            document.removeEventListener(
                'visibilitychange',
                onVisibilityChange,
            );
    }, []);

    const lowTime = remaining <= 60;

    return (
        <CandidateShell
            title={`${progress.currentLabel} - Placement Test`}
            wide
        >
            <div className="mb-4 rounded-xl border border-border bg-card p-4 shadow-sm">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <p className="text-xs font-medium text-muted-foreground">
                            Section {progress.currentIndex} of {progress.total}
                        </p>
                        <h1 className="text-lg font-semibold">
                            {progress.currentLabel}
                        </h1>
                    </div>
                    <div
                        className={`rounded-lg px-3 py-1.5 text-right font-mono text-lg font-semibold tabular-nums ${
                            lowTime
                                ? 'bg-destructive/10 text-destructive'
                                : 'bg-muted text-foreground'
                        }`}
                        aria-label="Time remaining"
                    >
                        {formatClock(remaining)}
                    </div>
                </div>

                <ol className="mt-3 flex gap-1.5">
                    {progress.sections.map((section) => (
                        <li
                            key={section.value}
                            className="flex-1"
                            title={section.label}
                        >
                            <div
                                className={`h-1.5 rounded-full ${
                                    section.status === 'completed'
                                        ? 'bg-primary'
                                        : section.value === progress.current
                                          ? 'bg-primary/40'
                                          : 'bg-muted'
                                }`}
                            />
                            <p className="mt-1 hidden truncate text-[10px] text-muted-foreground sm:block">
                                {section.label}
                            </p>
                        </li>
                    ))}
                </ol>

                <div className="mt-2 flex min-h-4 items-center justify-between">
                    <p className="text-xs text-muted-foreground">
                        {SAVE_LABEL[saveState]}
                    </p>
                </div>
            </div>

            {tabWarning && (
                <div
                    className="mb-4 flex items-start justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800"
                    role="alert"
                >
                    <p>
                        Leaving the test tab is recorded and flagged for GLC
                        staff review. Please stay on this page until you
                        finish.
                    </p>
                    <button
                        type="button"
                        onClick={() => setTabWarning(false)}
                        className="shrink-0 text-xs font-semibold underline"
                    >
                        Dismiss
                    </button>
                </div>
            )}

            {children}
        </CandidateShell>
    );
}
