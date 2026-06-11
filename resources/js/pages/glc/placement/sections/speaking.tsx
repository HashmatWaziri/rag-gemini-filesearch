import { useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { ErrorText, PrimaryButton } from '../components/candidate-shell';
import SectionShell from '../components/section-shell';
import { placementApi } from '../lib/placement-api';
import {
    pickRecorderMimeType,
    recorderSupported,
    recordingExtension,
} from '../lib/recorder';
import { analyzeRecording } from '../lib/recording-quality';
import { type SectionPageProps } from '../lib/types';

interface SpeakingProps extends SectionPageProps {
    prompt: {
        id: number;
        title: string | null;
        body: string | null;
        maxDurationSeconds: number;
        maxAttempts: number;
    } | null;
    recording: {
        attemptsUsed: number;
        hasRecording: boolean;
        durationSeconds: number | null;
    };
}

type Phase =
    | 'idle'
    | 'mic_blocked'
    | 'recording'
    | 'analyzing'
    | 'preview'
    | 'uploading';

export default function Speaking({
    progress,
    timer,
    config,
    prompt,
    recording,
}: SpeakingProps) {
    const maxDuration = prompt?.maxDurationSeconds ?? 180;
    const maxAttempts = prompt?.maxAttempts ?? 3;

    const [phase, setPhase] = useState<Phase>('idle');
    const [elapsed, setElapsed] = useState(0);
    const [attemptsUsed, setAttemptsUsed] = useState(recording.attemptsUsed);
    const [hasRecording, setHasRecording] = useState(recording.hasRecording);
    const [qualityMessage, setQualityMessage] = useState<string>();
    const [uploadError, setUploadError] = useState<string>();
    const [micError, setMicError] = useState<string>();

    const recorderRef = useRef<MediaRecorder | null>(null);
    const chunksRef = useRef<Blob[]>([]);
    const blobRef = useRef<Blob | null>(null);
    const durationRef = useRef(0);
    const previewUrlRef = useRef<string | undefined>(undefined);

    const submitForm = useForm({});

    useEffect(() => {
        if (phase !== 'recording') {
            return;
        }

        const tick = window.setInterval(() => {
            setElapsed((current) => {
                const next = current + 1;

                if (next >= maxDuration) {
                    stopRecording();
                }

                return next;
            });
        }, 1000);

        return () => window.clearInterval(tick);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [phase, maxDuration]);

    const startRecording = async () => {
        setQualityMessage(undefined);
        setUploadError(undefined);
        setMicError(undefined);

        if (!recorderSupported()) {
            setPhase('mic_blocked');
            setMicError(
                'Your browser does not support audio recording. Use an up-to-date version of Chrome, Safari, Firefox, or Edge, or contact GLC for help.',
            );
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
            });

            const mimeType = pickRecorderMimeType();
            const recorder = new MediaRecorder(
                stream,
                mimeType ? { mimeType } : undefined,
            );

            chunksRef.current = [];
            recorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    chunksRef.current.push(event.data);
                }
            };
            recorder.onstop = () => {
                stream.getTracks().forEach((track) => track.stop());
                void handleRecordingComplete(
                    new Blob(chunksRef.current, {
                        type: recorder.mimeType || 'audio/webm',
                    }),
                );
            };

            recorderRef.current = recorder;
            setElapsed(0);
            durationRef.current = 0;
            recorder.start();
            setPhase('recording');
        } catch {
            setPhase('mic_blocked');
            setMicError(
                'Microphone access was blocked or no microphone was found. Allow microphone access in your browser settings and try again. If your device has no microphone, contact GLC.',
            );
        }
    };

    const stopRecording = () => {
        setElapsed((current) => {
            durationRef.current = Math.max(1, current);
            return current;
        });

        if (recorderRef.current?.state === 'recording') {
            recorderRef.current.stop();
        }
    };

    const handleRecordingComplete = async (blob: Blob) => {
        setPhase('analyzing');
        blobRef.current = blob;

        const quality = await analyzeRecording(blob);

        if (!quality.passed) {
            // Failed quality checks do not count toward the attempt limit.
            setQualityMessage(quality.message);
            setPhase('idle');
            return;
        }

        if (previewUrlRef.current) {
            URL.revokeObjectURL(previewUrlRef.current);
        }
        previewUrlRef.current = URL.createObjectURL(blob);
        setPhase('preview');
    };

    const upload = async () => {
        const blob = blobRef.current;

        if (!blob) {
            return;
        }

        setPhase('uploading');
        setUploadError(undefined);

        const form = new FormData();
        form.append(
            'audio',
            blob,
            `speaking-response.${recordingExtension(blob.type)}`,
        );
        form.append('duration_seconds', String(durationRef.current));
        form.append('quality_passed', '1');

        const result = await placementApi.uploadRecording(form);

        if (!result.ok) {
            setUploadError(
                result.data.message ??
                    "We couldn't save your recording. Check your connection and try again.",
            );
            setPhase('preview');
            return;
        }

        setAttemptsUsed(result.data.attemptsUsed ?? attemptsUsed + 1);
        setHasRecording(true);
        setPhase('idle');
        blobRef.current = null;
    };

    const discardPreview = () => {
        blobRef.current = null;
        setPhase('idle');
    };

    const attemptsRemaining = Math.max(0, maxAttempts - attemptsUsed);
    const canRecord =
        attemptsRemaining > 0 && (phase === 'idle' || phase === 'mic_blocked');

    const formatElapsed = `${Math.floor(elapsed / 60)}:${String(elapsed % 60).padStart(2, '0')}`;

    return (
        <SectionShell progress={progress} timer={timer} config={config}>
            {prompt && (
                <div className="rounded-lg border border-slate-200 bg-white p-4">
                    {prompt.title && (
                        <h2 className="mb-1 font-semibold">{prompt.title}</h2>
                    )}
                    <p className="text-sm leading-relaxed text-slate-700">
                        {prompt.body}
                    </p>
                    <p className="mt-2 text-xs text-slate-500">
                        Speak for up to {Math.floor(maxDuration / 60)} minutes.
                        You have up to {maxAttempts} recording attempts - failed
                        quality checks do not count.
                    </p>
                </div>
            )}

            <div className="mt-4 rounded-lg border border-slate-200 bg-white p-4">
                <div className="flex items-center justify-between text-sm">
                    <span className="font-medium">
                        Attempts used: {attemptsUsed} / {maxAttempts}
                    </span>
                    {hasRecording && (
                        <span className="font-medium text-emerald-600">
                            Recording saved
                        </span>
                    )}
                </div>

                {qualityMessage && (
                    <div
                        className="mt-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800"
                        role="alert"
                    >
                        {qualityMessage}
                    </div>
                )}

                {micError && (
                    <div
                        className="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                        role="alert"
                    >
                        {micError}
                    </div>
                )}

                {phase === 'recording' && (
                    <div className="mt-4 text-center">
                        <p className="inline-flex items-center gap-2 font-mono text-2xl font-semibold tabular-nums">
                            <span className="h-3 w-3 animate-pulse rounded-full bg-red-500" />
                            {formatElapsed}
                        </p>
                        <p className="mt-1 text-xs text-slate-500">
                            Recording... speak clearly. Stops automatically at{' '}
                            {Math.floor(maxDuration / 60)} minutes.
                        </p>
                        <button
                            type="button"
                            onClick={stopRecording}
                            className="mt-3 w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700"
                        >
                            Stop recording
                        </button>
                    </div>
                )}

                {phase === 'analyzing' && (
                    <p className="mt-4 text-center text-sm text-slate-500">
                        Checking recording quality...
                    </p>
                )}

                {phase === 'preview' && (
                    <div className="mt-4 space-y-3">
                        <p className="text-sm text-emerald-700">
                            Quality check passed. Listen back, then save this
                            recording or record again.
                        </p>
                        {previewUrlRef.current && (
                            <audio
                                controls
                                src={previewUrlRef.current}
                                className="w-full"
                            />
                        )}
                        {uploadError && (
                            <p className="text-sm text-red-600">
                                {uploadError}
                            </p>
                        )}
                        <PrimaryButton
                            type="button"
                            onClick={() => void upload()}
                        >
                            Save this recording (uses 1 attempt)
                        </PrimaryButton>
                        <button
                            type="button"
                            onClick={discardPreview}
                            className="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600"
                        >
                            Discard and record again
                        </button>
                    </div>
                )}

                {phase === 'uploading' && (
                    <p className="mt-4 text-center text-sm text-slate-500">
                        Uploading your recording...
                    </p>
                )}

                {canRecord && (
                    <div className="mt-4">
                        <PrimaryButton
                            type="button"
                            onClick={() => void startRecording()}
                        >
                            {hasRecording
                                ? 'Record again (replaces saved recording)'
                                : 'Start recording'}
                        </PrimaryButton>
                    </div>
                )}

                {attemptsRemaining === 0 && !hasRecording && (
                    <p className="mt-3 text-sm text-red-600">
                        You have used all recording attempts. Contact GLC if you
                        were unable to submit a recording.
                    </p>
                )}
            </div>

            <div className="mt-6">
                <ErrorText
                    message={
                        (submitForm.errors as Record<string, string>).recording
                    }
                />
                <ErrorText
                    message={
                        (submitForm.errors as Record<string, string>).submit
                    }
                />
                <PrimaryButton
                    type="button"
                    disabled={!hasRecording || submitForm.processing}
                    onClick={() => submitForm.post('/placement/submit')}
                >
                    {submitForm.processing
                        ? 'Submitting...'
                        : 'Submit my placement test'}
                </PrimaryButton>
                {!hasRecording && (
                    <p className="mt-2 text-center text-xs text-slate-500">
                        Save a recording to enable submission.
                    </p>
                )}
            </div>
        </SectionShell>
    );
}
