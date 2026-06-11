import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEvent } from 'react';

interface SubmissionItem {
    id: number;
    status: string;
    excerpt: string;
    created_at: string | null;
}

interface Props {
    submissions: SubmissionItem[];
}

const STATUS_STYLES: Record<string, string> = {
    completed: 'bg-emerald-100 text-emerald-700',
    pending: 'bg-amber-100 text-amber-700',
    failed: 'bg-red-100 text-red-700',
};

export default function WritingIndex({ submissions }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        text: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post('/tutor/writing', { onSuccess: () => reset('text') });
    };

    return (
        <GlcLayout title="Writing correction">
            <Head title="Writing correction" />

            <p className="mb-4 text-sm text-slate-600">
                Submit a piece of English writing and get feedback on grammar,
                vocabulary, structure, coherence, and task completion, with
                inline highlights showing where to improve.
            </p>

            <form
                onSubmit={submit}
                className="mb-8 rounded-lg border border-slate-200 bg-white p-4"
            >
                <label
                    htmlFor="writing-text"
                    className="mb-2 block text-sm font-medium text-slate-700"
                >
                    Your writing
                </label>
                <textarea
                    id="writing-text"
                    value={data.text}
                    onChange={(event) => setData('text', event.target.value)}
                    rows={8}
                    maxLength={10000}
                    placeholder="Paste or type your English writing here (at least a few sentences)"
                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 focus:outline-none"
                />
                {errors.text && (
                    <p className="mt-1 text-sm text-red-600">{errors.text}</p>
                )}
                <div className="mt-3 flex items-center justify-between gap-3">
                    <p className="text-xs text-slate-400">
                        {data.text.length} / 10000 characters
                    </p>
                    <button
                        type="submit"
                        disabled={processing || data.text.trim().length < 20}
                        className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                    >
                        {processing
                            ? 'Evaluating your writing...'
                            : 'Submit for correction'}
                    </button>
                </div>
            </form>

            <h2 className="mb-2 text-sm font-semibold tracking-wide text-slate-500 uppercase">
                Your submissions
            </h2>

            {submissions.length === 0 ? (
                <p className="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-500">
                    No submissions yet.
                </p>
            ) : (
                <ul className="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white">
                    {submissions.map((submission) => (
                        <li key={submission.id}>
                            <Link
                                href={`/tutor/writing/${submission.id}`}
                                className="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-sm text-slate-900">
                                        {submission.excerpt}
                                    </p>
                                    <p className="mt-0.5 text-xs text-slate-400">
                                        {submission.created_at
                                            ? new Date(
                                                  submission.created_at,
                                              ).toLocaleString()
                                            : ''}
                                    </p>
                                </div>
                                <span
                                    className={`shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                        STATUS_STYLES[submission.status] ??
                                        'bg-slate-100 text-slate-600'
                                    }`}
                                >
                                    {submission.status}
                                </span>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </GlcLayout>
    );
}
