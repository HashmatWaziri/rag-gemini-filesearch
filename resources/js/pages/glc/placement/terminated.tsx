import CandidateShell, { Card } from './components/candidate-shell';

export default function Terminated() {
    return (
        <CandidateShell title="Test Ended">
            <Card>
                <h1 className="text-lg font-semibold">Your test was ended</h1>
                <p className="mt-2 text-sm text-slate-600">
                    This access code was used on more than one device at the
                    same time, so the test was ended for security reasons and
                    flagged for GLC staff.
                </p>
                <p className="mt-2 text-sm text-slate-600">
                    Please contact Greats Language Center to discuss the next
                    steps and receive a new access code if appropriate.
                </p>
            </Card>
        </CandidateShell>
    );
}
