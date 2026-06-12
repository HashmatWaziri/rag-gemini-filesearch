import { useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';
import CandidateShell, {
    Card,
    ErrorText,
    PrimaryButton,
} from './components/candidate-shell';

interface SectionInfo {
    value: string;
    label: string;
    order: number;
    estimatedMinutes: number;
}

interface InstructionsProps {
    candidateName: string;
    sections: SectionInfo[];
    listeningAutoStartSeconds: number;
}

export default function Instructions({
    candidateName,
    sections,
    listeningAutoStartSeconds,
}: InstructionsProps) {
    const form = useForm({ acknowledged: false });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/placement/instructions');
    };

    const totalMinutes = sections.reduce(
        (sum, section) => sum + section.estimatedMinutes,
        0,
    );

    return (
        <CandidateShell title="Test Instructions">
            <Card>
                <h1 className="text-lg font-semibold">
                    Before you start, {candidateName}
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    The placement test has five sections in a fixed order. Each
                    section has its own timer. Total time is about{' '}
                    {totalMinutes} minutes.
                </p>

                <ol className="mt-4 space-y-2">
                    {sections.map((section) => (
                        <li
                            key={section.value}
                            className="flex items-center justify-between rounded-lg border border-border px-3 py-2 text-sm"
                        >
                            <span>
                                <span className="mr-2 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary/10 text-xs font-semibold text-primary">
                                    {section.order}
                                </span>
                                {section.label}
                            </span>
                            <span className="text-muted-foreground">
                                ~{section.estimatedMinutes} min
                            </span>
                        </li>
                    ))}
                </ol>

                <div className="mt-4 space-y-2 rounded-lg bg-muted/50 p-3 text-sm text-muted-foreground">
                    <p className="font-medium text-foreground">
                        Important notes
                    </p>
                    <ul className="list-disc space-y-1 pl-4">
                        <li>
                            Your answers are saved automatically. If you lose
                            connection briefly, you will not lose your work.
                        </li>
                        <li>
                            You can pause and resume within 24 hours on this
                            same device.
                        </li>
                        <li>
                            Listening clips play only once each - find a quiet
                            place before starting. The first clip starts
                            automatically about {listeningAutoStartSeconds}{' '}
                            seconds after the Listening section opens, so be
                            ready to listen as soon as you get there.
                        </li>
                        <li>
                            The Speaking section records your voice on this
                            device. On a phone, hold it about 20 cm from your
                            mouth, speak clearly, and allow microphone access
                            when your browser asks.
                        </li>
                        <li>
                            Stay in this browser tab. Switching tabs is recorded
                            for GLC staff review.
                        </li>
                    </ul>
                </div>

                <form onSubmit={submit} className="mt-4 space-y-3">
                    <label className="flex items-start gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={form.data.acknowledged}
                            onChange={(event) =>
                                form.setData(
                                    'acknowledged',
                                    event.target.checked,
                                )
                            }
                            className="mt-0.5 rounded border-input"
                        />
                        <span>
                            I have read the instructions and I am ready to
                            continue.
                        </span>
                    </label>
                    <ErrorText message={form.errors.acknowledged} />
                    <PrimaryButton
                        disabled={!form.data.acknowledged || form.processing}
                    >
                        Continue to audio setup
                    </PrimaryButton>
                </form>
            </Card>
        </CandidateShell>
    );
}
