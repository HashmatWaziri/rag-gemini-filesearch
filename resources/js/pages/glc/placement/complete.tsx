import CandidateShell, { Card } from './components/candidate-shell';

interface CompleteProps {
    candidateName: string;
    submittedAt: string | null;
}

/**
 * Pending-review screen. Candidates see only this confirmation -
 * results are released by GLC staff after their review.
 */
export default function Complete({ candidateName }: CompleteProps) {
    return (
        <CandidateShell title="Test Submitted">
            <Card>
                <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100">
                    <svg
                        className="h-5 w-5 text-emerald-600"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            fillRule="evenodd"
                            d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.415 0l-3.5-3.5a1 1 0 111.415-1.42l2.792 2.793 6.793-6.793a1 1 0 011.415 0z"
                            clipRule="evenodd"
                        />
                    </svg>
                </div>
                <h1 className="text-lg font-semibold">
                    Thank you, {candidateName}. Your test has been submitted.
                </h1>
                <p className="mt-2 text-sm text-slate-600">
                    Your placement test is now pending review by GLC staff.
                    There is nothing more you need to do.
                </p>
                <p className="mt-2 text-sm text-slate-600">
                    Greats Language Center will contact you with your official
                    Placement Test Result once the review is complete.
                </p>
            </Card>
        </CandidateShell>
    );
}
