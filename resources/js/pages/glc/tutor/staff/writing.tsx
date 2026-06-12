import GlcLayout from '@/layouts/glc-layout';
import { Head, Link } from '@inertiajs/react';
import WritingFeedback, {
    type SubmissionPayload,
} from '../components/writing-feedback';

interface Props {
    student: { id: number; name: string };
    submission: SubmissionPayload;
}

export default function StaffTutorWriting({ student, submission }: Props) {
    return (
        <GlcLayout title={`Writing - ${student.name}`}>
            <Head title={`Writing - ${student.name}`} />

            <Link
                href={`/staff/tutor/students/${student.id}`}
                className="mb-4 inline-block text-sm font-medium text-primary hover:underline"
            >
                Back to {student.name}
            </Link>

            <WritingFeedback submission={submission} />
        </GlcLayout>
    );
}
