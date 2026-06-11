import { useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import CandidateShell, {
    Card,
    ErrorText,
    PrimaryButton,
} from './components/candidate-shell';
import { placementApi } from './lib/placement-api';

type Step = 'code' | 'privacy' | 'profile';

interface EntryProps {
    minimumAge: number;
}

export default function Entry({ minimumAge }: EntryProps) {
    const [step, setStep] = useState<Step>('code');
    const [codeError, setCodeError] = useState<string>();
    const [checking, setChecking] = useState(false);
    const [privacyAccepted, setPrivacyAccepted] = useState(false);

    const form = useForm({
        code: '',
        privacy_acknowledged: false,
        name: '',
        email: '',
        age: '',
    });

    const checkCode = async (event: FormEvent) => {
        event.preventDefault();
        setCodeError(undefined);
        setChecking(true);

        const result = await placementApi.validateCode(form.data.code);
        setChecking(false);

        if (result.data.redirect) {
            window.location.href = result.data.redirect;
            return;
        }

        if (result.ok && result.data.valid) {
            setStep('privacy');
            return;
        }

        setCodeError(result.data.message ?? 'Unable to validate the code.');
    };

    const acceptPrivacy = () => {
        form.setData('privacy_acknowledged', true);
        setStep('profile');
    };

    const submitProfile = (event: FormEvent) => {
        event.preventDefault();
        form.post('/placement/start');
    };

    return (
        <CandidateShell title="Start Placement Test">
            {step === 'code' && (
                <Card>
                    <h1 className="text-lg font-semibold">
                        Welcome to the GLC Placement Test
                    </h1>
                    <p className="mt-1 text-sm text-slate-600">
                        Enter the access code you received from Greats Language
                        Center to begin.
                    </p>
                    <form onSubmit={checkCode} className="mt-4 space-y-3">
                        <div>
                            <label
                                htmlFor="access-code"
                                className="mb-1 block text-sm font-medium"
                            >
                                Access code
                            </label>
                            <input
                                id="access-code"
                                type="text"
                                value={form.data.code}
                                onChange={(event) =>
                                    form.setData(
                                        'code',
                                        event.target.value.toUpperCase(),
                                    )
                                }
                                autoComplete="off"
                                autoCapitalize="characters"
                                spellCheck={false}
                                className="w-full rounded-lg border border-slate-300 px-3 py-2.5 font-mono text-base tracking-widest uppercase focus:border-emerald-500 focus:ring-emerald-500"
                                placeholder="XXXXXXXX"
                            />
                            <ErrorText message={codeError} />
                        </div>
                        <PrimaryButton
                            disabled={checking || form.data.code.length === 0}
                        >
                            {checking ? 'Checking...' : 'Continue'}
                        </PrimaryButton>
                    </form>
                </Card>
            )}

            {step === 'privacy' && (
                <Card>
                    <h1 className="text-lg font-semibold">Privacy Notice</h1>
                    <div className="mt-3 space-y-3 text-sm text-slate-600">
                        <p>
                            Greats Language Center (GLC) collects your name,
                            email address, age, test responses, and speaking
                            recording to assess your English proficiency and
                            deliver your placement result. Your information is
                            handled in line with the Personal Data Protection
                            Act (PDPA) and is only accessible to authorised GLC
                            staff.
                        </p>
                        <p className="font-medium text-slate-700">
                            Your data is not used to train AI models.
                        </p>
                        <p>
                            Your responses are stored securely and reviewed by
                            GLC staff before any result is released. Contact
                            GLC if you have questions about how your data is
                            used.
                        </p>
                    </div>
                    <label className="mt-4 flex items-start gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={privacyAccepted}
                            onChange={(event) =>
                                setPrivacyAccepted(event.target.checked)
                            }
                            className="mt-0.5 rounded border-slate-300"
                        />
                        <span>
                            I have read and understood the privacy notice.
                        </span>
                    </label>
                    <div className="mt-4">
                        <PrimaryButton
                            type="button"
                            disabled={!privacyAccepted}
                            onClick={acceptPrivacy}
                        >
                            I agree, continue
                        </PrimaryButton>
                    </div>
                </Card>
            )}

            {step === 'profile' && (
                <Card>
                    <h1 className="text-lg font-semibold">Your details</h1>
                    <p className="mt-1 text-sm text-slate-600">
                        GLC uses these details to deliver your result.
                        Candidates under {minimumAge} cannot take this test.
                    </p>
                    <form onSubmit={submitProfile} className="mt-4 space-y-3">
                        <div>
                            <label
                                htmlFor="name"
                                className="mb-1 block text-sm font-medium"
                            >
                                Full name
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base"
                                autoComplete="name"
                            />
                            <ErrorText message={form.errors.name} />
                        </div>
                        <div>
                            <label
                                htmlFor="email"
                                className="mb-1 block text-sm font-medium"
                            >
                                Email address
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                className="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base"
                                autoComplete="email"
                            />
                            <ErrorText message={form.errors.email} />
                        </div>
                        <div>
                            <label
                                htmlFor="age"
                                className="mb-1 block text-sm font-medium"
                            >
                                Age
                            </label>
                            <input
                                id="age"
                                type="number"
                                inputMode="numeric"
                                min={3}
                                max={120}
                                value={form.data.age}
                                onChange={(event) =>
                                    form.setData('age', event.target.value)
                                }
                                className="w-32 rounded-lg border border-slate-300 px-3 py-2.5 text-base"
                            />
                            <ErrorText message={form.errors.age} />
                        </div>
                        <ErrorText message={form.errors.code} />
                        <ErrorText
                            message={
                                (form.errors as Record<string, string>)
                                    .privacy_acknowledged
                            }
                        />
                        <PrimaryButton disabled={form.processing}>
                            {form.processing ? 'Starting...' : 'Start the test'}
                        </PrimaryButton>
                    </form>
                </Card>
            )}
        </CandidateShell>
    );
}
