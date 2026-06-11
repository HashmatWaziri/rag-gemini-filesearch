import { useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import CandidateShell, {
    Card,
    PrimaryButton,
} from './components/candidate-shell';
import { placementApi } from './lib/placement-api';
import {
    pickRecorderMimeType,
    recorderSupported,
    recordingExtension,
} from './lib/recorder';
import { analyzeRecording } from './lib/recording-quality';
import { speakerTestToneUri } from './lib/test-tone';

interface DeviceCheckProps {
    recordingMaxSeconds: number;
}

type WizardStep = 'speaker' | 'microphone' | 'recording';

const STEPS: { value: WizardStep; label: string }[] = [
    { value: 'speaker', label: 'Speakers' },
    { value: 'microphone', label: 'Microphone' },
    { value: 'recording', label: 'Test recording' },
];

/**
 * Three-step audio setup before the test starts:
 *
 * 1. Speaker check - pick an output device (where the browser allows it)
 *    and play a real test sound (also needed by Listening).
 * 2. Microphone check - pick an input device and watch a live level meter.
 * 3. Test recording - record up to ~10 seconds; the server transcribes it
 *    (Gemini) and the candidate confirms "here's what we heard" plus a
 *    local playback, proving the whole chain works end to end.
 */
export default function DeviceCheck({ recordingMaxSeconds }: DeviceCheckProps) {
    const [step, setStep] = useState<WizardStep>('speaker');
    const [micDeviceId, setMicDeviceId] = useState<string>();

    const stepIndex = STEPS.findIndex((entry) => entry.value === step);

    return (
        <CandidateShell title="Audio Setup">
            <Card>
                <h1 className="text-lg font-semibold">Audio setup</h1>
                <p className="mt-1 text-sm text-slate-600">
                    The test plays audio (Listening) and records your voice
                    (Speaking). Let&apos;s make sure both work before you start.
                </p>

                <ol className="mt-4 flex gap-1.5">
                    {STEPS.map((entry, index) => (
                        <li key={entry.value} className="flex-1">
                            <div
                                className={`h-1.5 rounded-full ${
                                    index < stepIndex
                                        ? 'bg-emerald-500'
                                        : index === stepIndex
                                          ? 'bg-emerald-300'
                                          : 'bg-slate-200'
                                }`}
                            />
                            <p className="mt-1 truncate text-[10px] text-slate-500">
                                {index + 1}. {entry.label}
                            </p>
                        </li>
                    ))}
                </ol>

                <div className="mt-4">
                    {step === 'speaker' && (
                        <SpeakerStep onConfirm={() => setStep('microphone')} />
                    )}
                    {step === 'microphone' && (
                        <MicrophoneStep
                            onBack={() => setStep('speaker')}
                            onConfirm={(deviceId) => {
                                setMicDeviceId(deviceId);
                                setStep('recording');
                            }}
                        />
                    )}
                    {step === 'recording' && (
                        <RecordingStep
                            micDeviceId={micDeviceId}
                            maxSeconds={recordingMaxSeconds}
                            onBack={() => setStep('microphone')}
                        />
                    )}
                </div>
            </Card>
        </CandidateShell>
    );
}

async function listAudioDevices(
    kind: MediaDeviceKind,
): Promise<MediaDeviceInfo[]> {
    if (!navigator.mediaDevices?.enumerateDevices) {
        return [];
    }

    try {
        const devices = await navigator.mediaDevices.enumerateDevices();

        return devices.filter(
            (device) =>
                device.kind === kind &&
                device.deviceId !== '' &&
                device.label !== '',
        );
    } catch {
        return [];
    }
}

function useAudioDevices(kind: MediaDeviceKind): {
    devices: MediaDeviceInfo[];
    refresh: () => void;
} {
    const [devices, setDevices] = useState<MediaDeviceInfo[]>([]);

    const refresh = () => {
        void listAudioDevices(kind).then(setDevices);
    };

    useEffect(() => {
        refresh();

        const media = navigator.mediaDevices;

        if (!media?.addEventListener) {
            return;
        }

        const onChange = () => refresh();
        media.addEventListener('devicechange', onChange);

        return () => media.removeEventListener('devicechange', onChange);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [kind]);

    return { devices, refresh };
}

interface SinkCapableAudioElement extends HTMLAudioElement {
    setSinkId(sinkId: string): Promise<void>;
}

function supportsOutputSelection(
    audio: HTMLAudioElement,
): audio is SinkCapableAudioElement {
    return 'setSinkId' in audio;
}

function SpeakerStep({ onConfirm }: { onConfirm: () => void }) {
    const { devices: outputs } = useAudioDevices('audiooutput');
    const [outputId, setOutputId] = useState('');
    const [playState, setPlayState] = useState<'idle' | 'playing' | 'played'>(
        'idle',
    );
    const [cannotHear, setCannotHear] = useState(false);
    const [error, setError] = useState<string>();
    const audioRef = useRef<HTMLAudioElement | null>(null);

    useEffect(() => {
        return () => {
            audioRef.current?.pause();
        };
    }, []);

    const playTone = async () => {
        setError(undefined);

        const audio = audioRef.current ?? new Audio();
        audioRef.current = audio;
        audio.src = speakerTestToneUri();

        if (outputId !== '' && supportsOutputSelection(audio)) {
            try {
                await audio.setSinkId(outputId);
            } catch {
                // Fall back to the default output device.
            }
        }

        audio.onended = () => setPlayState('played');

        try {
            await audio.play();
            setPlayState('playing');
        } catch {
            setError(
                'Your browser blocked the test sound. Click "Play test sound" again, and check that this tab is not muted.',
            );
        }
    };

    const canSelectOutput =
        outputs.length > 0 && supportsOutputSelection(new Audio());

    return (
        <div className="space-y-3">
            <h2 className="font-semibold">Step 1: Can you hear the sound?</h2>

            {canSelectOutput ? (
                <label className="block text-sm">
                    <span className="mb-1 block text-slate-600">
                        Output device (speakers / headphones)
                    </span>
                    <select
                        value={outputId}
                        onChange={(event) => {
                            setOutputId(event.target.value);
                            setPlayState('idle');
                        }}
                        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">System default</option>
                        {outputs.map((device) => (
                            <option
                                key={device.deviceId}
                                value={device.deviceId}
                            >
                                {device.label}
                            </option>
                        ))}
                    </select>
                </label>
            ) : (
                <p className="text-xs text-slate-500">
                    Your browser chooses the output device automatically. Use
                    your device&apos;s volume and sound settings if you need to
                    switch speakers or headphones.
                </p>
            )}

            <button
                type="button"
                onClick={() => void playTone()}
                className="w-full rounded-lg border border-emerald-600 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50"
            >
                {playState === 'playing'
                    ? 'Playing...'
                    : playState === 'played'
                      ? 'Play test sound again'
                      : 'Play test sound'}
            </button>

            {error && <p className="text-sm text-red-600">{error}</p>}

            {cannotHear && (
                <div className="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
                    <p className="font-semibold">No sound? Try this:</p>
                    <ul className="mt-1 list-disc space-y-1 pl-4">
                        <li>Turn up the volume and unmute your device.</li>
                        <li>
                            Unplug and reconnect headphones, or pick another
                            output device above.
                        </li>
                        <li>Play the test sound again after each change.</li>
                    </ul>
                    <p className="mt-1">
                        Still nothing? Try another device or browser, or contact
                        GLC for help.
                    </p>
                </div>
            )}

            <PrimaryButton
                type="button"
                disabled={playState === 'idle'}
                onClick={onConfirm}
            >
                I can hear the sound - continue
            </PrimaryButton>
            <button
                type="button"
                onClick={() => setCannotHear(true)}
                className="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600"
            >
                I can&apos;t hear anything
            </button>
        </div>
    );
}

function MicrophoneStep({
    onBack,
    onConfirm,
}: {
    onBack: () => void;
    onConfirm: (deviceId: string | undefined) => void;
}) {
    const { devices: inputs, refresh } = useAudioDevices('audioinput');
    const [inputId, setInputId] = useState('');
    const [listening, setListening] = useState(false);
    const [level, setLevel] = useState(0);
    const [voiceDetected, setVoiceDetected] = useState(false);
    const [error, setError] = useState<string>();

    const streamRef = useRef<MediaStream | null>(null);
    const contextRef = useRef<AudioContext | null>(null);
    const rafRef = useRef<number | undefined>(undefined);

    const stopMeter = () => {
        if (rafRef.current !== undefined) {
            cancelAnimationFrame(rafRef.current);
            rafRef.current = undefined;
        }

        streamRef.current?.getTracks().forEach((track) => track.stop());
        streamRef.current = null;
        void contextRef.current?.close().catch(() => {});
        contextRef.current = null;
        setListening(false);
        setLevel(0);
    };

    useEffect(() => stopMeter, []);

    const startMeter = async (deviceId: string) => {
        stopMeter();
        setError(undefined);

        if (!recorderSupported()) {
            setError(
                'Your browser does not support audio recording. Please use an up-to-date version of Chrome, Safari, Firefox, or Edge.',
            );
            return;
        }

        let stream: MediaStream;

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                audio:
                    deviceId === '' ? true : { deviceId: { exact: deviceId } },
            });
        } catch {
            setError(
                'Microphone access was blocked or no microphone was found. Allow microphone access in your browser settings, then try again.',
            );
            return;
        }

        streamRef.current = stream;
        refresh(); // labels become available once permission is granted

        const ContextCtor = window.AudioContext;

        if (typeof ContextCtor === 'undefined') {
            // No level meter support - the recording step still verifies it.
            setListening(true);
            setVoiceDetected(true);
            return;
        }

        const context = new ContextCtor();
        contextRef.current = context;
        const analyser = context.createAnalyser();
        analyser.fftSize = 1024;
        context.createMediaStreamSource(stream).connect(analyser);

        const data = new Float32Array(analyser.fftSize);

        const loop = () => {
            analyser.getFloatTimeDomainData(data);

            let sum = 0;
            for (let i = 0; i < data.length; i++) {
                sum += data[i] * data[i];
            }

            const rms = Math.sqrt(sum / data.length);
            setLevel(rms);

            if (rms > 0.02) {
                setVoiceDetected(true);
            }

            rafRef.current = requestAnimationFrame(loop);
        };

        setListening(true);
        loop();
    };

    const confirm = () => {
        stopMeter();
        onConfirm(inputId === '' ? undefined : inputId);
    };

    return (
        <div className="space-y-3">
            <h2 className="font-semibold">Step 2: Test your microphone</h2>

            {inputs.length > 0 && (
                <label className="block text-sm">
                    <span className="mb-1 block text-slate-600">
                        Input device (microphone)
                    </span>
                    <select
                        value={inputId}
                        onChange={(event) => {
                            setInputId(event.target.value);

                            if (listening) {
                                void startMeter(event.target.value);
                            }
                        }}
                        className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">System default</option>
                        {inputs.map((device) => (
                            <option
                                key={device.deviceId}
                                value={device.deviceId}
                            >
                                {device.label}
                            </option>
                        ))}
                    </select>
                </label>
            )}

            {!listening && (
                <button
                    type="button"
                    onClick={() => void startMeter(inputId)}
                    className="w-full rounded-lg border border-emerald-600 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50"
                >
                    Enable my microphone
                </button>
            )}

            {listening && (
                <div>
                    <p className="text-sm text-slate-600">
                        Say something like{' '}
                        <span className="font-medium">
                            &quot;Testing, one two three&quot;
                        </span>{' '}
                        - the bar should move while you speak.
                    </p>
                    <div className="mt-2 h-3 overflow-hidden rounded-full bg-slate-200">
                        <div
                            className={`h-full rounded-full transition-[width] duration-75 ${
                                voiceDetected
                                    ? 'bg-emerald-500'
                                    : 'bg-amber-400'
                            }`}
                            style={{
                                width: `${Math.min(100, level * 600)}%`,
                            }}
                        />
                    </div>
                    <p className="mt-1 text-xs text-slate-500">
                        {voiceDetected
                            ? 'Your microphone is picking up sound.'
                            : 'Waiting for sound... speak a little louder or move closer to the microphone.'}
                    </p>
                </div>
            )}

            {error && <p className="text-sm text-red-600">{error}</p>}

            <PrimaryButton
                type="button"
                disabled={!listening || !voiceDetected}
                onClick={confirm}
            >
                My microphone works - continue
            </PrimaryButton>
            <button
                type="button"
                onClick={() => {
                    stopMeter();
                    onBack();
                }}
                className="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600"
            >
                Back to speaker check
            </button>
        </div>
    );
}

type RecordingPhase =
    | { kind: 'idle' }
    | { kind: 'recording'; elapsed: number }
    | { kind: 'checking' }
    | { kind: 'transcribing' }
    | {
          kind: 'result';
          transcript: string | null;
          uploadFailed: boolean;
      };

function RecordingStep({
    micDeviceId,
    maxSeconds,
    onBack,
}: {
    micDeviceId: string | undefined;
    maxSeconds: number;
    onBack: () => void;
}) {
    const [phase, setPhase] = useState<RecordingPhase>({ kind: 'idle' });
    const [qualityMessage, setQualityMessage] = useState<string>();
    const [error, setError] = useState<string>();
    const [previewUrl, setPreviewUrl] = useState<string>();

    const recorderRef = useRef<MediaRecorder | null>(null);
    const chunksRef = useRef<Blob[]>([]);
    const blobRef = useRef<Blob | null>(null);
    const durationRef = useRef(0);

    const finishForm = useForm({
        audio_ok: true,
        microphone_ok: true,
        recording_ok: true,
    });

    useEffect(() => {
        return () => {
            if (recorderRef.current?.state === 'recording') {
                recorderRef.current.stop();
            }
        };
    }, []);

    useEffect(() => {
        if (phase.kind !== 'recording') {
            return;
        }

        const tick = window.setInterval(() => {
            setPhase((current) => {
                if (current.kind !== 'recording') {
                    return current;
                }

                const elapsed = current.elapsed + 1;

                if (elapsed >= maxSeconds) {
                    stopRecording(elapsed);
                }

                return { kind: 'recording', elapsed };
            });
        }, 1000);

        return () => window.clearInterval(tick);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [phase.kind, maxSeconds]);

    const startRecording = async () => {
        setQualityMessage(undefined);
        setError(undefined);

        if (!recorderSupported()) {
            setError(
                'Your browser does not support audio recording. Please use an up-to-date version of Chrome, Safari, Firefox, or Edge.',
            );
            return;
        }

        let stream: MediaStream;

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                audio: micDeviceId
                    ? { deviceId: { exact: micDeviceId } }
                    : true,
            });
        } catch {
            try {
                // Selected device may have been unplugged - fall back.
                stream = await navigator.mediaDevices.getUserMedia({
                    audio: true,
                });
            } catch {
                setError(
                    'Microphone access was blocked. Allow microphone access in your browser settings and try again.',
                );
                return;
            }
        }

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
        durationRef.current = 0;
        recorder.start();
        setPhase({ kind: 'recording', elapsed: 0 });
    };

    const stopRecording = (elapsed: number) => {
        durationRef.current = Math.min(maxSeconds, Math.max(1, elapsed));

        if (recorderRef.current?.state === 'recording') {
            recorderRef.current.stop();
        }
    };

    const handleRecordingComplete = async (blob: Blob) => {
        setPhase({ kind: 'checking' });
        blobRef.current = blob;

        const quality = await analyzeRecording(blob);

        if (!quality.passed) {
            setQualityMessage(quality.message);
            setPhase({ kind: 'idle' });
            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
        setPreviewUrl(URL.createObjectURL(blob));

        await transcribe(blob);
    };

    const transcribe = async (blob: Blob) => {
        setPhase({ kind: 'transcribing' });
        setError(undefined);

        const form = new FormData();
        form.append(
            'audio',
            blob,
            `mic-check.${recordingExtension(blob.type)}`,
        );
        form.append('duration_seconds', String(durationRef.current));

        const result = await placementApi.transcribeMicCheck(form);

        if (!result.ok) {
            setError(
                result.data.message ??
                    'We could not check your recording. You can try again, or listen to the playback below to confirm it.',
            );
            setPhase({
                kind: 'result',
                transcript: null,
                uploadFailed: true,
            });
            return;
        }

        setPhase({
            kind: 'result',
            transcript: result.data.transcript ?? null,
            uploadFailed: false,
        });
    };

    const reset = () => {
        blobRef.current = null;
        setQualityMessage(undefined);
        setError(undefined);
        setPhase({ kind: 'idle' });
    };

    return (
        <div className="space-y-3">
            <h2 className="font-semibold">Step 3: Test your setup</h2>
            <p className="text-sm text-slate-600">
                Record yourself for up to {maxSeconds} seconds - for example,
                say your name and why you are taking this test. We will show you
                what we heard.
            </p>

            {qualityMessage && (
                <div
                    className="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800"
                    role="alert"
                >
                    {qualityMessage}
                </div>
            )}

            {error && <p className="text-sm text-red-600">{error}</p>}

            {phase.kind === 'idle' && (
                <PrimaryButton
                    type="button"
                    onClick={() => void startRecording()}
                >
                    Start recording
                </PrimaryButton>
            )}

            {phase.kind === 'recording' && (
                <div className="text-center">
                    <p className="inline-flex items-center gap-2 font-mono text-2xl font-semibold tabular-nums">
                        <span className="h-3 w-3 animate-pulse rounded-full bg-red-500" />
                        0:{String(phase.elapsed).padStart(2, '0')}
                    </p>
                    <p className="mt-1 text-xs text-slate-500">
                        Recording... stops automatically at {maxSeconds}{' '}
                        seconds.
                    </p>
                    <button
                        type="button"
                        onClick={() => stopRecording(phase.elapsed)}
                        className="mt-3 w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700"
                    >
                        Stop recording
                    </button>
                </div>
            )}

            {phase.kind === 'checking' && (
                <p className="text-center text-sm text-slate-500">
                    Checking recording quality...
                </p>
            )}

            {phase.kind === 'transcribing' && (
                <p className="text-center text-sm text-slate-500">
                    Analyzing your recording...
                </p>
            )}

            {phase.kind === 'result' && (
                <div className="space-y-3">
                    {phase.transcript !== null ? (
                        <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                            <p className="text-xs font-semibold tracking-wide text-emerald-700 uppercase">
                                Here&apos;s what we heard
                            </p>
                            <p className="mt-1 text-sm text-emerald-900">
                                &ldquo;{phase.transcript}&rdquo;
                            </p>
                        </div>
                    ) : (
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                            Automatic transcription is not available right now.
                            Play your recording below - if you can hear yourself
                            clearly, your setup is ready.
                        </div>
                    )}

                    {previewUrl && (
                        <audio controls src={previewUrl} className="w-full" />
                    )}

                    {phase.uploadFailed && blobRef.current && (
                        <button
                            type="button"
                            onClick={() => {
                                const blob = blobRef.current;

                                if (blob) {
                                    void transcribe(blob);
                                }
                            }}
                            className="w-full rounded-lg border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50"
                        >
                            Try the analysis again
                        </button>
                    )}

                    <PrimaryButton
                        type="button"
                        disabled={finishForm.processing}
                        onClick={() =>
                            finishForm.post('/placement/device-check')
                        }
                    >
                        {finishForm.processing
                            ? 'Finishing setup...'
                            : 'Finish setup - start the test'}
                    </PrimaryButton>
                    <button
                        type="button"
                        onClick={reset}
                        className="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600"
                    >
                        Record again
                    </button>
                </div>
            )}

            {(phase.kind === 'idle' || phase.kind === 'result') && (
                <button
                    type="button"
                    onClick={onBack}
                    className="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600"
                >
                    Back to microphone check
                </button>
            )}
        </div>
    );
}
