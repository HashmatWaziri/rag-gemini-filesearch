import GlcLayout from '@/layouts/glc-layout';
import { Head, Link } from '@inertiajs/react';

interface StudentRow {
    id: number;
    name: string;
    email: string;
    conversation_count: number;
    last_active_at: string | null;
}

interface Props {
    students: StudentRow[];
}

export default function StaffTutorIndex({ students }: Props) {
    return (
        <GlcLayout title="Tutor Activity">
            <Head title="Tutor Activity" />

            <p className="mb-4 text-sm text-slate-600">
                Tutor usage per student: last active date and conversation
                count. Open a student to read their conversations, see their
                writing, and check anything that needs attention.
            </p>

            {students.length === 0 ? (
                <p className="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-500">
                    No students to show.
                </p>
            ) : (
                <ul className="divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white">
                    {students.map((student) => (
                        <li key={student.id}>
                            <Link
                                href={`/staff/tutor/students/${student.id}`}
                                className="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium text-slate-900">
                                        {student.name}
                                    </p>
                                    <p className="truncate text-xs text-slate-500">
                                        {student.email}
                                    </p>
                                </div>
                                <div className="shrink-0 text-right">
                                    <p className="text-sm text-slate-700">
                                        {student.conversation_count}{' '}
                                        conversation
                                        {student.conversation_count === 1
                                            ? ''
                                            : 's'}
                                    </p>
                                    <p className="text-xs text-slate-400">
                                        {student.last_active_at
                                            ? `Last active ${new Date(student.last_active_at).toLocaleDateString()}`
                                            : 'Never active'}
                                    </p>
                                </div>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </GlcLayout>
    );
}
