/**
 * Shared MediaRecorder helpers for the speaking section and the
 * pre-test microphone check.
 */
const RECORDER_MIME_CANDIDATES = [
    'audio/webm;codecs=opus',
    'audio/webm',
    'audio/mp4',
    'audio/ogg;codecs=opus',
];

export function recorderSupported(): boolean {
    return (
        Boolean(navigator.mediaDevices?.getUserMedia) &&
        typeof window.MediaRecorder !== 'undefined'
    );
}

export function pickRecorderMimeType(): string | undefined {
    if (typeof window.MediaRecorder === 'undefined') {
        return undefined;
    }

    return RECORDER_MIME_CANDIDATES.find((candidate) =>
        MediaRecorder.isTypeSupported(candidate),
    );
}

export function recordingExtension(mimeType: string): string {
    if (mimeType.includes('mp4')) {
        return 'm4a';
    }

    if (mimeType.includes('ogg')) {
        return 'ogg';
    }

    if (mimeType.includes('wav')) {
        return 'wav';
    }

    return 'webm';
}
