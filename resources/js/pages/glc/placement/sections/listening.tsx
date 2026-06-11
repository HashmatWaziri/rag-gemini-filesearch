import { useEffect, useRef, useState } from 'react';
import FinishSection from '../components/finish-section';
import McqList from '../components/mcq-list';
import SectionShell from '../components/section-shell';
import { sharedAudioElement, unlockAudioElement } from '../lib/audio-unlock';
import { placementApi } from '../lib/placement-api';
import { type McqQuestion, type SectionPageProps } from '../lib/types';
import { useObjectiveAnswers } from '../lib/use-objective-answers';
import { useUnansweredReview } from '../lib/use-unanswered-review';

interface Clip {
    id: number;
    position: number;
    title: string | null;
    played: boolean;
    questions: McqQuestion[];
}

interface ListeningProps extends SectionPageProps {
    clips: Clip[];
    answers: Record<number, number>;
}

type Playback =
    | { kind: 'idle' }
    | { kind: 'loading' }
    | { kind: 'blocked' }
    | { kind: 'playing' }
    | { kind: 'done' };

export default function Listening({
    progress,
    timer,
    config,
    clips,
    answers: initialAnswers,
}: ListeningProps) {
    const { answers, select, saveState } = useObjectiveAnswers(initialAnswers);

    const allQuestions = clips.flatMap((clip) => clip.questions);
    const { highlightUnanswered, unansweredCount, reviewUnanswered } =
        useUnansweredReview(allQuestions, answers);

    // Only the first unplayed clip auto-starts; later clips stay manual so
    // the candidate controls when they are ready after answering.
    const autoStartClipId = clips.find((clip) => !clip.played)?.id;

    // One clip at a time: while a clip is audible the others cannot start.
    const [playingClipId, setPlayingClipId] = useState<number | null>(null);

    const reportPlaying = (clipId: number, isPlaying: boolean) => {
        setPlayingClipId((current) =>
            isPlaying ? clipId : current === clipId ? null : current,
        );
    };

    let questionNumber = 1;

    return (
        <SectionShell
            progress={progress}
            timer={timer}
            config={config}
            saveState={saveState}
        >
            <div
                className="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800"
                role="alert"
            >
                <p className="font-semibold">Each clip plays only once.</p>
                <p>
                    There is no replay. Make sure your volume is up and you are
                    in a quiet place. The first clip starts automatically -
                    later clips start when you press play.
                </p>
            </div>

            <div className="space-y-6">
                {clips.map((clip, index) => {
                    const startNumber = questionNumber;
                    questionNumber += clip.questions.length;

                    return (
                        <ClipBlock
                            key={clip.id}
                            clip={clip}
                            index={index}
                            answers={answers}
                            onSelect={select}
                            startNumber={startNumber}
                            highlightUnanswered={highlightUnanswered}
                            autoStartSeconds={
                                clip.id === autoStartClipId
                                    ? config.listeningAutoStartSeconds
                                    : null
                            }
                            lockPlay={
                                playingClipId !== null &&
                                playingClipId !== clip.id
                            }
                            onPlayingChange={(isPlaying) =>
                                reportPlaying(clip.id, isPlaying)
                            }
                        />
                    );
                })}
            </div>

            <FinishSection
                section={progress.current}
                nextLabel="Writing"
                unansweredCount={unansweredCount}
                onReviewUnanswered={reviewUnanswered}
            />
        </SectionShell>
    );
}

function ClipBlock({
    clip,
    index,
    answers,
    onSelect,
    startNumber,
    highlightUnanswered,
    autoStartSeconds,
    lockPlay,
    onPlayingChange,
}: {
    clip: Clip;
    index: number;
    answers: Record<number, number>;
    onSelect: (itemId: number, selected: number) => void;
    startNumber: number;
    highlightUnanswered: boolean;
    autoStartSeconds: number | null;
    lockPlay: boolean;
    onPlayingChange: (isPlaying: boolean) => void;
}) {
    const [playback, setPlayback] = useState<Playback>(
        clip.played ? { kind: 'done' } : { kind: 'idle' },
    );
    const [countdown, setCountdown] = useState<number | null>(
        !clip.played && autoStartSeconds !== null ? autoStartSeconds : null,
    );
    const [error, setError] = useState<string>();
    const audioRef = useRef<HTMLAudioElement | null>(null);

    useEffect(() => {
        return () => {
            audioRef.current?.pause();
        };
    }, []);

    useEffect(() => {
        onPlayingChange(playback.kind === 'playing');
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [playback.kind]);

    /**
     * Registers the one-time play, then streams through the given element.
     * The element must already be unlocked (played inside a gesture) for
     * playback to succeed without a fresh click; when the browser still
     * blocks it, the clip is NOT lost - the fetched URL stays on the
     * element and the candidate gets a "start listening" button.
     */
    const beginPlayback = async (audio: HTMLAudioElement) => {
        setError(undefined);
        setPlayback({ kind: 'loading' });
        audioRef.current = audio;

        const result = await placementApi.registerPlay(clip.id);

        if (!result.ok || !result.data.url) {
            setPlayback({ kind: 'done' });
            setError(
                result.data.message ??
                    'This clip is no longer available to play.',
            );
            return;
        }

        audio.onended = () => setPlayback({ kind: 'done' });
        audio.onerror = () => {
            setPlayback({ kind: 'done' });
            setError(
                'Audio playback failed. If you did not hear the clip, contact GLC.',
            );
        };
        audio.src = result.data.url;

        try {
            await audio.play();
            setPlayback({ kind: 'playing' });
        } catch {
            setPlayback({ kind: 'blocked' });
        }
    };

    // Auto-start countdown for the first unplayed clip. If another clip is
    // somehow audible when it reaches zero, it waits instead of overlapping.
    useEffect(() => {
        if (countdown === null) {
            return;
        }

        if (countdown <= 0) {
            if (lockPlay) {
                // Effect re-runs when lockPlay clears, starting the clip.
                return;
            }

            setCountdown(null);
            void beginPlayback(sharedAudioElement());
            return;
        }

        const timeout = window.setTimeout(
            () =>
                setCountdown((current) =>
                    current === null ? null : current - 1,
                ),
            1000,
        );

        return () => window.clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [countdown, lockPlay]);

    const startNow = () => {
        setCountdown(null);
        const audio = sharedAudioElement();
        unlockAudioElement(audio);
        void beginPlayback(audio);
    };

    const playManually = () => {
        const audio = new Audio();
        unlockAudioElement(audio);
        void beginPlayback(audio);
    };

    const resumeBlocked = async () => {
        const audio = audioRef.current;

        if (!audio) {
            return;
        }

        try {
            await audio.play();
            setPlayback({ kind: 'playing' });
        } catch {
            setError(
                'Your browser blocked audio playback. Check your sound settings and contact GLC if the problem continues.',
            );
        }
    };

    const questionsEnabled =
        playback.kind === 'done' || playback.kind === 'playing';

    return (
        <section className="rounded-lg border border-slate-200 bg-white p-4">
            <div className="flex items-center justify-between gap-3">
                <h2 className="font-semibold">
                    Clip {index + 1}
                    {clip.title ? ` - ${clip.title}` : ''}
                </h2>
                {countdown === null && playback.kind === 'idle' && (
                    <button
                        type="button"
                        onClick={playManually}
                        disabled={lockPlay}
                        className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                    >
                        Play once
                    </button>
                )}
                {playback.kind === 'loading' && (
                    <span className="text-sm text-slate-500">Loading...</span>
                )}
                {playback.kind === 'playing' && (
                    <span className="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-700">
                        <span className="h-2 w-2 animate-pulse rounded-full bg-emerald-600" />
                        Playing - listen carefully
                    </span>
                )}
                {playback.kind === 'done' && (
                    <span className="text-sm font-medium text-slate-400">
                        Played
                    </span>
                )}
            </div>

            {countdown !== null && (
                <div
                    className="mt-3 rounded-lg border border-emerald-300 bg-emerald-50 p-3"
                    role="status"
                >
                    <p className="text-sm font-semibold text-emerald-800">
                        Get ready - this clip starts in {countdown}{' '}
                        {countdown === 1 ? 'second' : 'seconds'}.
                    </p>
                    <p className="mt-0.5 text-xs text-emerald-700">
                        It plays only once. Put your sound on now.
                    </p>
                    <button
                        type="button"
                        onClick={startNow}
                        disabled={lockPlay}
                        className="mt-2 rounded-lg bg-emerald-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                    >
                        I&apos;m ready - start now
                    </button>
                </div>
            )}

            {playback.kind === 'blocked' && (
                <div
                    className="mt-3 rounded-lg border border-amber-300 bg-amber-50 p-3"
                    role="alert"
                >
                    <p className="text-sm font-semibold text-amber-800">
                        Press the button to hear the clip.
                    </p>
                    <p className="mt-0.5 text-xs text-amber-700">
                        Your browser needs one more tap before it can play
                        audio. The clip has not played yet.
                    </p>
                    <button
                        type="button"
                        onClick={() => void resumeBlocked()}
                        className="mt-2 rounded-lg bg-emerald-600 px-4 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        Start listening now
                    </button>
                </div>
            )}

            {error && <p className="mt-2 text-sm text-red-600">{error}</p>}

            {!questionsEnabled && (
                <p className="mt-2 text-xs text-slate-500">
                    The questions unlock when the clip plays.
                </p>
            )}

            <div className="mt-4">
                <McqList
                    questions={clip.questions}
                    answers={answers}
                    onSelect={onSelect}
                    startNumber={startNumber}
                    disabled={!questionsEnabled}
                    highlightUnanswered={highlightUnanswered}
                />
            </div>
        </section>
    );
}
