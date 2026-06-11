import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { primeAudioPlayback } from '../lib/audio-unlock';
import { ErrorText, PrimaryButton } from './candidate-shell';

interface FinishSectionProps {
    section: string;
    nextLabel: string | null;
    unansweredCount?: number;
    onReviewUnanswered?: () => void;
    disabled?: boolean;
    disabledReason?: string;
}

/**
 * Explicit "Finish section" action. Sections are fixed-order: finishing
 * one unlocks the next. The first press with unanswered questions jumps
 * to and highlights them (via onReviewUnanswered) instead of finishing;
 * the candidate can then either answer them or press "Finish anyway".
 */
export default function FinishSection({
    section,
    nextLabel,
    unansweredCount = 0,
    onReviewUnanswered,
    disabled = false,
    disabledReason,
}: FinishSectionProps) {
    const [confirming, setConfirming] = useState(false);
    const form = useForm({ section });

    // Once every question is answered, drop back to the normal finish
    // button so the candidate is not asked to "finish anyway".
    useEffect(() => {
        if (unansweredCount === 0) {
            setConfirming(false);
        }
    }, [unansweredCount]);

    const submit = () => {
        // Unlock shared audio while we still have a user gesture so the
        // next section (Listening) can auto-start its first clip.
        primeAudioPlayback();
        form.post('/placement/section/complete');
    };

    const handleClick = () => {
        if (unansweredCount > 0 && !confirming) {
            setConfirming(true);
            onReviewUnanswered?.();
            return;
        }

        submit();
    };

    return (
        <div className="mt-6 space-y-2">
            {confirming && unansweredCount > 0 && (
                <div
                    className="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800"
                    role="alert"
                >
                    You have {unansweredCount} unanswered{' '}
                    {unansweredCount === 1 ? 'question' : 'questions'} - they
                    are highlighted above. Answer them, or press &ldquo;Finish
                    anyway&rdquo;. You cannot return to this section after
                    finishing.
                </div>
            )}
            <ErrorText message={form.errors.section} />
            <ErrorText
                message={(form.errors as Record<string, string>).words}
            />
            {disabled && disabledReason && (
                <p className="text-sm text-slate-500">{disabledReason}</p>
            )}
            <PrimaryButton
                type="button"
                disabled={disabled || form.processing}
                onClick={handleClick}
            >
                {confirming && unansweredCount > 0
                    ? 'Finish anyway'
                    : nextLabel
                      ? `Finish section - continue to ${nextLabel}`
                      : 'Finish section'}
            </PrimaryButton>
            {confirming && unansweredCount > 0 && (
                <button
                    type="button"
                    onClick={() => {
                        setConfirming(false);
                        onReviewUnanswered?.();
                    }}
                    className="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600"
                >
                    Go back to my answers
                </button>
            )}
        </div>
    );
}
