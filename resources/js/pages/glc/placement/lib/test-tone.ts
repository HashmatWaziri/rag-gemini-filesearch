import { wavDataUri } from './wav';

let cached: string | null = null;

/**
 * Two-note chime (~1.8s) used for the speaker check, generated at runtime.
 */
export function speakerTestToneUri(): string {
    if (cached) {
        return cached;
    }

    const sampleRate = 22050;
    const noteSeconds = 0.9;
    const notes = [659.25, 880]; // E5 then A5
    const samples = new Float32Array(
        Math.floor(sampleRate * noteSeconds * notes.length),
    );

    notes.forEach((frequency, noteIndex) => {
        const start = Math.floor(noteIndex * noteSeconds * sampleRate);
        const length = Math.floor(noteSeconds * sampleRate);

        for (let i = 0; i < length; i++) {
            const t = i / sampleRate;
            // Soft attack, exponential decay, one octave harmonic for warmth.
            const envelope = Math.min(1, t / 0.02) * Math.exp(-3 * t);
            const tone =
                Math.sin(2 * Math.PI * frequency * t) +
                0.35 * Math.sin(2 * Math.PI * frequency * 2 * t);
            samples[start + i] += 0.45 * envelope * tone;
        }
    });

    cached = wavDataUri(samples, sampleRate);

    return cached;
}
