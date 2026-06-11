import { Head, usePage } from '@inertiajs/react';

interface PageProps {
    candidateName: string;
    testDate: string | null;
    overallLevel: string | null;
    skillLevels: { skill: string; level: string | null }[];
    narrative: {
        strengths: string | null;
        areas_to_improve: string | null;
        recommendation: string | null;
        next_steps: string | null;
    };
    downloadUrl: string;
    expiresAt: string;
    [key: string]: unknown;
}

const NARRATIVE_LABELS: Record<string, string> = {
    strengths: 'Strengths',
    areas_to_improve: 'Areas to Improve',
    recommendation: 'Recommendation',
    next_steps: 'Next Steps',
};

/**
 * Public mobile-first result page reached via the secure 30-day link.
 * Shows levels + approved narrative only — no scores, notes, or AI data.
 */
export default function ResultPublic() {
    const {
        candidateName,
        testDate,
        overallLevel,
        skillLevels,
        narrative,
        downloadUrl,
        expiresAt,
    } = usePage<PageProps>().props;

    return (
        <div className="min-h-screen bg-slate-50 px-4 py-8 text-slate-900">
            <Head title="Placement Test Result" />
            <div className="mx-auto max-w-lg">
                <div className="mb-6 flex items-center gap-3">
                    <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-600 text-sm font-bold text-white">
                        GLC
                    </span>
                    <div>
                        <p className="text-sm font-semibold">
                            Greats Language Center
                        </p>
                        <p className="text-xs text-slate-500">
                            English Language Placement
                        </p>
                    </div>
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h1 className="text-xl font-bold">
                        Placement Test Result
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        {candidateName}
                        {testDate && ` · tested on ${testDate}`}
                    </p>

                    <div className="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-center">
                        <p className="text-xs font-medium tracking-wide text-emerald-700 uppercase">
                            Overall GLC Level
                        </p>
                        <p className="mt-1 text-2xl font-bold text-emerald-800">
                            {overallLevel ?? '—'}
                        </p>
                    </div>

                    <table className="mt-5 w-full text-sm">
                        <thead>
                            <tr className="border-b border-slate-200 text-left text-xs text-slate-500 uppercase">
                                <th className="py-2">Skill</th>
                                <th className="py-2 text-right">GLC Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            {skillLevels.map((row) => (
                                <tr
                                    key={row.skill}
                                    className="border-b border-slate-100"
                                >
                                    <td className="py-2">{row.skill}</td>
                                    <td className="py-2 text-right font-medium">
                                        {row.level ?? '—'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <div className="mt-5 space-y-4">
                        {Object.entries(NARRATIVE_LABELS).map(
                            ([key, label]) => {
                                const text =
                                    narrative[key as keyof typeof narrative];

                                return text ? (
                                    <div key={key}>
                                        <h2 className="text-sm font-semibold text-slate-800">
                                            {label}
                                        </h2>
                                        <p className="mt-1 text-sm leading-relaxed text-slate-600">
                                            {text}
                                        </p>
                                    </div>
                                ) : null;
                            },
                        )}
                    </div>

                    <a
                        href={downloadUrl}
                        className="mt-6 block w-full rounded-md bg-emerald-600 py-2.5 text-center text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        Download PDF
                    </a>
                </div>

                <p className="mt-4 text-center text-xs text-slate-400">
                    This link is valid until {expiresAt}. For questions or
                    enrollment, please contact Greats Language Center.
                </p>
            </div>
        </div>
    );
}
