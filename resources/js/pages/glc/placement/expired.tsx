import CandidateShell, { Card } from './components/candidate-shell';

interface ExpiredProps {
    resumeWindowHours: number;
}

export default function Expired({ resumeWindowHours }: ExpiredProps) {
    return (
        <CandidateShell title="Test Expired">
            <Card>
                <h1 className="text-lg font-semibold">Your test has expired</h1>
                <p className="mt-2 text-sm text-slate-600">
                    You can return to an unfinished test within{' '}
                    {resumeWindowHours} hours of starting it. That time has now
                    passed, so this test is closed.
                </p>
                <p className="mt-2 text-sm text-slate-600">
                    Please contact Greats Language Center to receive a new
                    access code and take the test again.
                </p>
            </Card>
        </CandidateShell>
    );
}
