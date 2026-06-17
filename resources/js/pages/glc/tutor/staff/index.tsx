import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { Head, Link, router } from '@inertiajs/react';

interface AssignmentSummary {
    course: string;
    level: string;
    unit: string;
}

interface StudentRow {
    id: number;
    name: string;
    email: string;
    conversation_count: number;
    writing_submission_count: number;
    recent_violations_count: number;
    last_active_at: string | null;
    assignment: AssignmentSummary | null;
    needs_attention: boolean;
}

interface WeakAreaRow {
    dimension?: string;
    label: string;
    average_score?: number;
    count?: number;
}

interface Props {
    students: StudentRow[];
    canViewAll: boolean;
    progressAnalyticsEnabled: boolean;
    cohortWeakAreas: {
        writing_dimensions: WeakAreaRow[];
        violation_categories: WeakAreaRow[];
    } | null;
    filters: {
        sort: string;
        inactive_days: number | null;
    };
}

function formatLastActive(value: string | null): string {
    if (!value) {
        return 'Never active';
    }

    const date = new Date(value);
    const diffDays = Math.floor(
        (Date.now() - date.getTime()) / (1000 * 60 * 60 * 24),
    );

    if (diffDays === 0) {
        return 'Today';
    }

    if (diffDays === 1) {
        return 'Yesterday';
    }

    if (diffDays < 14) {
        return `${diffDays} days ago`;
    }

    return date.toLocaleDateString();
}

function applyFilters(sort: string, inactiveDays: number | '') {
    router.get(
        '/staff/tutor',
        {
            sort,
            inactive_days: inactiveDays === '' ? undefined : inactiveDays,
        },
        { preserveScroll: true, preserveState: true },
    );
}

export default function StaffTutorIndex({
    students,
    canViewAll,
    progressAnalyticsEnabled,
    cohortWeakAreas,
    filters,
}: Props) {
    return (
        <GlcLayout title="Tutor Activity">
            <Head title="Tutor Activity" />

            <p className="mb-4 text-sm text-secondary-foreground">
                Usage & progress for {canViewAll ? 'all enrolled students' : 'your linked students'}.
                Open a student to read conversations, writing, and anything that needs attention.
            </p>

            <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end">
                <label className="flex flex-col gap-1 text-xs text-muted-foreground">
                    Sort by
                    <select
                        className="rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                        value={filters.sort}
                        onChange={(event) =>
                            applyFilters(
                                event.target.value,
                                filters.inactive_days ?? '',
                            )
                        }
                    >
                        <option value="last_active">Last active (oldest first)</option>
                        <option value="violations">Needs attention</option>
                        <option value="name">Name</option>
                    </select>
                </label>
                <label className="flex flex-col gap-1 text-xs text-muted-foreground">
                    Show inactive
                    <select
                        className="rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground"
                        value={filters.inactive_days ?? ''}
                        onChange={(event) =>
                            applyFilters(
                                filters.sort,
                                event.target.value
                                    ? Number(event.target.value)
                                    : '',
                            )
                        }
                    >
                        <option value="">All students</option>
                        <option value="14">Inactive 14+ days</option>
                        <option value="30">Inactive 30+ days</option>
                    </select>
                </label>
            </div>

            {progressAnalyticsEnabled && cohortWeakAreas && (
                <div className="mb-6 rounded-xl border border-border bg-card p-4">
                    <h2 className="mb-2 text-sm font-semibold text-foreground">
                        Cohort focus areas (last 30 days)
                    </h2>
                    {cohortWeakAreas.writing_dimensions.length === 0 &&
                    cohortWeakAreas.violation_categories.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Not enough tutor activity yet to highlight cohort patterns.
                        </p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {cohortWeakAreas.writing_dimensions
                                .slice(0, 3)
                                .map((row) => (
                                    <Badge key={row.label} variant="outline">
                                        {row.label} avg {row.average_score}/5
                                    </Badge>
                                ))}
                            {cohortWeakAreas.violation_categories
                                .slice(0, 3)
                                .map((row) => (
                                    <Badge
                                        key={row.label}
                                        variant="destructive"
                                    >
                                        {row.label}: {row.count}
                                    </Badge>
                                ))}
                        </div>
                    )}
                </div>
            )}

            {students.length === 0 ? (
                <p className="rounded-xl border border-border bg-card px-5 py-8 text-center text-sm text-muted-foreground">
                    No students match these filters.
                </p>
            ) : (
                <>
                    <div className="space-y-3 sm:hidden">
                        {students.map((student) => (
                            <div
                                key={student.id}
                                className="rounded-xl border border-border bg-card p-4"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="font-medium text-mono">
                                            {student.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {student.email}
                                        </p>
                                    </div>
                                    {student.needs_attention && (
                                        <Badge variant="destructive">
                                            Needs attention
                                        </Badge>
                                    )}
                                </div>
                                <p className="mt-2 text-sm text-secondary-foreground">
                                    {student.conversation_count} conversations ·{' '}
                                    {student.writing_submission_count} writing ·{' '}
                                    {formatLastActive(student.last_active_at)}
                                </p>
                                {student.assignment ? (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {student.assignment.course} /{' '}
                                        {student.assignment.level} /{' '}
                                        {student.assignment.unit}
                                    </p>
                                ) : (
                                    <p className="mt-1 text-xs text-amber-700 dark:text-amber-400">
                                        No course set yet
                                    </p>
                                )}
                                <Link
                                    href={`/staff/tutor/students/${student.id}`}
                                    className="mt-3 inline-block text-sm font-medium text-primary hover:underline"
                                >
                                    Open
                                </Link>
                            </div>
                        ))}
                    </div>

                    <GlcDataTableCard className="hidden sm:block">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/50 hover:bg-muted/50">
                                    <TableHead className="text-xs uppercase">
                                        Student
                                    </TableHead>
                                    <TableHead className="text-xs uppercase">
                                        Assignment
                                    </TableHead>
                                    <TableHead className="text-xs uppercase">
                                        Usage
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
                                            {student.needs_attention && (
                                                <Badge
                                                    className="mt-1"
                                                    variant="destructive"
                                                >
                                                    Needs attention (
                                                    {student.recent_violations_count}
                                                    )
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-sm text-secondary-foreground">
                                            {student.assignment ? (
                                                <>
                                                    {student.assignment.course}{' '}
                                                    / {student.assignment.level}{' '}
                                                    / {student.assignment.unit}
                                                </>
                                            ) : (
                                                <span className="text-amber-700 dark:text-amber-400">
                                                    No course set
                                                </span>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-sm text-secondary-foreground">
                                            {student.conversation_count}{' '}
                                            conversations
                                            <br />
                                            {student.writing_submission_count}{' '}
                                            writing
                                        </TableCell>
                                        <TableCell className="text-sm text-muted-foreground">
                                            {formatLastActive(
                                                student.last_active_at,
                                            )}
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
                </>
            )}
        </GlcLayout>
    );
}
