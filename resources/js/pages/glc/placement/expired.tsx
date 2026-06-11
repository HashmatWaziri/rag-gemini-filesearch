import CandidateShell, { Card } from './components/candidate-shell';

interface ExpiredProps {
    resumeWindowHours: number;
}

export default function Expired({ resumeWindowHours }: ExpiredProps) {
    return (
        <CandidateShell title="Session Expired">
            <Card>
                <h1 className="text-lg font-semibold">
                    Your test session has expired
                </h1>
                <p className="mt-2 text-sm text-slate-600">
                    Placement test sessions can be resumed within{' '}
                    {resumeWindowHours} hours of starting. That window has now
                    passed, so this session is closed.
                </p>
                <p className="mt-2 text-sm text-slate-600">
                    Please contact Greats Language Center to receive a new
                    access code and take the test again.
                </p>
            </Card>
        </CandidateShell>
    );
}
