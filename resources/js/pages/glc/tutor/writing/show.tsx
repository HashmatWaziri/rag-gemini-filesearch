import GlcLayout from '@/layouts/glc-layout';
import { Head, Link } from '@inertiajs/react';
import WritingFeedback, {
    type SubmissionPayload,
} from '../components/writing-feedback';

interface Props {
    submission: SubmissionPayload;
}

export default function WritingShow({ submission }: Props) {
    return (
        <GlcLayout title="Writing feedback">
            <Head title="Writing feedback" />

            <Link
                href="/tutor/writing"
                className="mb-4 inline-block text-sm font-medium text-emerald-700 hover:underline"
            >
                Back to writing correction
            </Link>

            <WritingFeedback submission={submission} />

            {submission.status === 'failed' && (
                <div className="mt-4">
                    <Link
                        href="/tutor/writing"
                        className="text-sm font-medium text-emerald-700 hover:underline"
                    >
                        Submit it again
                    </Link>
                </div>
            )}
        </GlcLayout>
    );
}
