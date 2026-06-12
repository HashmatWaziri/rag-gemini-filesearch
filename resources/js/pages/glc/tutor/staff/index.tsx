import { GlcDataTableCard } from '@/components/glc';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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

            <p className="mb-4 text-sm text-secondary-foreground">
                Tutor usage per student: last active date and conversation
                count. Open a student to read their conversations, see their
                writing, and check anything that needs attention.
            </p>

            {students.length === 0 ? (
                <p className="rounded-xl border border-border bg-card px-5 py-8 text-center text-sm text-muted-foreground">
                    No students to show.
                </p>
            ) : (
                <GlcDataTableCard>
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead className="text-xs uppercase">
                                    Student
                                </TableHead>
                                <TableHead className="text-xs uppercase">
                                    Conversations
                                </TableHead>
                                <TableHead className="text-xs uppercase">
                                    Last active
                                </TableHead>
                                <TableHead className="text-xs uppercase">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {students.map((student) => (
                                <TableRow
                                    key={student.id}
                                    className="hover:bg-accent/50"
                                >
                                    <TableCell>
                                        <p className="font-medium text-mono">
                                            {student.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {student.email}
                                        </p>
                                    </TableCell>
                                    <TableCell className="text-secondary-foreground">
                                        {student.conversation_count}{' '}
                                        {student.conversation_count === 1
                                            ? 'conversation'
                                            : 'conversations'}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {student.last_active_at
                                            ? new Date(
                                                  student.last_active_at,
                                              ).toLocaleDateString()
                                            : 'Never active'}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Link
                                            href={`/staff/tutor/students/${student.id}`}
                                            className="text-sm font-medium text-primary hover:underline"
                                        >
                                            Open
                                        </Link>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </GlcDataTableCard>
            )}
        </GlcLayout>
    );
}
