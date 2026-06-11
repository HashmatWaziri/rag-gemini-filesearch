export interface QualityResult {
    passed: boolean;
    reason?: 'silent' | 'too_quiet' | 'distorted';
    message?: string;
}

/**
 * Client-side recording quality check using WebAudio analysis.
 * Silent / too-quiet detection uses RMS and peak thresholds; clipping
 * (distortion) uses the ratio of near-full-scale samples. Failed checks
 * never count toward the submission attempt limit.
 */
export async function analyzeRecording(blob: Blob): Promise<QualityResult> {
    const AudioContextImpl =
        window.AudioContext ??
        (window as unknown as { webkitAudioContext?: typeof AudioContext })
            .webkitAudioContext;

    if (!AudioContextImpl) {
        // No analysis support - do not block the candidate.
        return { passed: true };
    }

    const context = new AudioContextImpl();

    try {
        const buffer = await context.decodeAudioData(await blob.arrayBuffer());
        const samples = buffer.getChannelData(0);

        let sumOfSquares = 0;
        let peak = 0;
        let clippedSamples = 0;

        for (let i = 0; i < samples.length; i++) {
            const amplitude = Math.abs(samples[i]);
            sumOfSquares += amplitude * amplitude;

            if (amplitude > peak) {
                peak = amplitude;
            }

            if (amplitude >= 0.985) {
                clippedSamples++;
            }
        }

        const rms = Math.sqrt(sumOfSquares / samples.length);
        const clippedRatio = clippedSamples / samples.length;

        if (peak < 0.01) {
            return {
                passed: false,
                reason: 'silent',
                message:
                    'Your recording is silent. Check that your microphone is not muted and try again. This try does not count.',
            };
        }

        if (rms < 0.015) {
            return {
                passed: false,
                reason: 'too_quiet',
                message:
                    'Your recording is too quiet. Move closer to the microphone and speak up, then try again. This try does not count.',
            };
        }

        if (clippedRatio > 0.02) {
            return {
                passed: false,
                reason: 'distorted',
                message:
                    'Your recording is distorted. Move slightly away from the microphone and speak normally, then try again. This try does not count.',
            };
        }

        return { passed: true };
    } catch {
        // Could not decode for analysis - let the upload proceed rather
        // than trapping the candidate.
        return { passed: true };
    } finally {
        void context.close();
    }
}
