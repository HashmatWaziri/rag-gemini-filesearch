import { silenceDataUri } from './wav';

/**
 * Browsers only allow audio playback that is tied to a user gesture. Once
 * an HTMLMediaElement has played inside a gesture, later programmatic
 * plays on that same element are allowed. These helpers exploit that:
 *
 * - `unlockAudioElement` plays a tiny silent clip synchronously inside the
 *   current gesture so the element can later play a fetched audio URL.
 * - `primeAudioPlayback` unlocks a module-level shared element. Inertia
 *   navigations keep the JS context alive, so priming it on the
 *   "Finish section" click lets the Listening section auto-start its
 *   first clip without another click.
 */
let sharedAudio: HTMLAudioElement | null = null;
let primed = false;

export function unlockAudioElement(audio: HTMLAudioElement): void {
    audio.src = silenceDataUri();
    void audio.play().catch(() => {
        // Unlock is best-effort; real playback handles its own failures.
    });
}

export function primeAudioPlayback(): void {
    sharedAudio ??= new Audio();
    unlockAudioElement(sharedAudio);
    primed = true;
}

export function sharedAudioElement(): HTMLAudioElement {
    sharedAudio ??= new Audio();

    return sharedAudio;
}

export function audioPlaybackPrimed(): boolean {
    return primed;
}
