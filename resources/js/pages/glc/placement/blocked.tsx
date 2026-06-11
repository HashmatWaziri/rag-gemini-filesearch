import CandidateShell, { Card } from './components/candidate-shell';

interface BlockedProps {
    minimumAge: number;
}

export default function Blocked({ minimumAge }: BlockedProps) {
    return (
        <CandidateShell title="Unable to Start">
            <Card>
                <h1 className="text-lg font-semibold">
                    We cannot start your test
                </h1>
                <p className="mt-2 text-sm text-slate-600">
                    This placement test is for candidates aged {minimumAge} and
                    above. Please contact Greats Language Center directly and
                    our team will help you find the right option.
                </p>
            </Card>
        </CandidateShell>
    );
}
