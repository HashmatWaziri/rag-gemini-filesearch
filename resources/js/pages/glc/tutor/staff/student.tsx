import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import GlcLayout from '@/layouts/glc-layout';
import { Head, Link, router } from '@inertiajs/react';

interface AssignmentSummary {
    course: string;
    level: string;
    unit: string;
}

interface ConversationRow {
    id: number;
    title: string | null;
    message_count: number;
    last_activity_at: string | null;
    summary: string | null;
}

interface SubmissionRow {
    id: number;
    status: string;
    excerpt: string;
    created_at: string | null;
}

interface ViolationRow {
    id: number;
    category: string;
    category_label: string;
    excerpt: string | null;
    occurred_at: string;
}

interface WeakAreaDimension {
    dimension: string;
    label: string;
    average_score: number;
    sample_count: number;
}

interface WeakAreaViolation {
    category: string;
    label: string;
    count: number;
}

interface ProgressReportRow {
    id: number;
    status: string;
    period_start: string;
    period_end: string;
    payload: {
        summary?: string;
        strengths?: string[];
        focus_areas?: string[];
        engagement_note?: string;
    } | null;
    error: string | null;
    created_at: string | null;
}

interface Props {
    student: { id: number; name: string; email: string };
    assignment: AssignmentSummary | null;
    activity: {
        conversation_count: number;
        last_active_at: string | null;
        writing_submission_count: number;
        recent_violations_count: number;
        active_minutes_last_30_days: number | null;
    };
    latestTopicsSummary: string | null;
    weakAreas: {
        writing_dimensions: WeakAreaDimension[];
        violation_categories: WeakAreaViolation[];
        has_enough_writing: boolean;
    } | null;
    progressReports: ProgressReportRow[];
    conversations: ConversationRow[];
    writingSubmissions: SubmissionRow[];
    violations: ViolationRow[];
    progressAnalyticsEnabled: boolean;
}

function formatDateTime(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '';
}

const SUBMISSION_STATUS_LABELS: Record<string, string> = {
    completed: 'Feedback ready',
    pending: 'Being checked',
    failed: 'Could not be checked',
};

export default function StaffTutorStudent({
    student,
    assignment,
    activity,
    latestTopicsSummary,
    weakAreas,
    progressReports,
    conversations,
    writingSubmissions,
    violations,
    progressAnalyticsEnabled,
}: Props) {
    const generateReport = () => {
        router.post(
            `/staff/tutor/students/${student.id}/progress-report`,
            {},
            { preserveScroll: true },
        );
    };

    return (
        <GlcLayout title={student.name}>
            <Head title={`Tutor activity - ${student.name}`} />

            <Link
                href="/staff/tutor"
                className="mb-4 inline-block text-sm font-medium text-primary hover:underline"
            >
                Back to Tutor Activity
            </Link>

            {assignment ? (
                <div className="mb-4 rounded-lg border border-border bg-card p-4">
                    <p className="text-xs text-muted-foreground">Current assignment</p>
                    <p className="text-sm font-semibold text-foreground">
                        {assignment.course} / {assignment.level} / {assignment.unit}
                    </p>
                    <Link
                        href="/staff/students"
                        className="mt-2 inline-block text-xs font-medium text-primary hover:underline"
                    >
                        Change assignment in My Students
                    </Link>
                </div>
            ) : (
                <div className="mb-4 rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-800 dark:text-amber-300">
                    No course assignment yet.{' '}
                    <Link href="/staff/students" className="font-medium underline">
                        Set one in My Students
                    </Link>{' '}
                    before expecting tutor usage.
                </div>
            )}

            <div className="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div className="rounded-lg border border-border bg-card p-4">
                    <p className="text-xs text-muted-foreground">Conversations</p>
                    <p className="text-xl font-semibold text-foreground">
                        {activity.conversation_count}
                    </p>
                </div>
                <div className="rounded-lg border border-border bg-card p-4">
                    <p className="text-xs text-muted-foreground">Last active</p>
                    <p className="text-sm font-semibold text-foreground">
                        {activity.last_active_at
                            ? new Date(activity.last_active_at).toLocaleDateString()
                            : 'Never'}
                    </p>
                </div>
                <div className="rounded-lg border border-border bg-card p-4">
                    <p className="text-xs text-muted-foreground">Writing submissions</p>
                    <p className="text-xl font-semibold text-foreground">
                        {activity.writing_submission_count}
                    </p>
                </div>
                <div className="rounded-lg border border-border bg-card p-4">
                    <p className="text-xs text-muted-foreground">Needs attention (30d)</p>
                    <p className="text-xl font-semibold text-foreground">
                        {activity.recent_violations_count}
                    </p>
                </div>
            </div>

            {progressAnalyticsEnabled &&
                activity.active_minutes_last_30_days !== null && (
                    <p className="mb-4 text-sm text-muted-foreground">
                        Approximate active time (last 30 days):{' '}
                        <span className="font-medium text-foreground">
                            {activity.active_minutes_last_30_days} minutes
                        </span>
                    </p>
                )}

            {latestTopicsSummary && (
                <section className="mb-6 rounded-lg border border-border bg-card p-4">
                    <h2 className="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Latest topics covered
                    </h2>
                    <p className="text-sm text-secondary-foreground whitespace-pre-wrap">
                        {latestTopicsSummary}
                    </p>
                </section>
            )}

            {progressAnalyticsEnabled && weakAreas && (
                <section className="mb-6 rounded-lg border border-border bg-card p-4">
                    <h2 className="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                        Focus areas (last 30 days)
                    </h2>
                    {!weakAreas.has_enough_writing &&
                    weakAreas.violation_categories.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Not enough tutor activity yet for weak-area signals.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {weakAreas.writing_dimensions.length > 0 && (
                                <ul className="space-y-1 text-sm text-secondary-foreground">
                                    {weakAreas.writing_dimensions.map((row) => (
                                        <li key={row.dimension}>
                                            {row.label}: average {row.average_score}/5 (
                                            {row.sample_count} submissions)
                                        </li>
                                    ))}
                                </ul>
                            )}
                            {weakAreas.violation_categories.length > 0 && (
                                <div className="flex flex-wrap gap-2">
                                    {weakAreas.violation_categories.map((row) => (
                                        <Badge key={row.category} variant="outline">
                                            {row.label}: {row.count}
                                        </Badge>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </section>
            )}

            {progressAnalyticsEnabled && (
                <section className="mb-6 rounded-lg border border-border bg-card p-4">
                    <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                            Progress reports
                        </h2>
                        <Button type="button" size="sm" onClick={generateReport}>
                            Generate report
                        </Button>
                    </div>
                    {progressReports.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No progress reports yet.
                        </p>
                    ) : (
                        <ul className="space-y-4">
                            {progressReports.map((report) => (
                                <li
                                    key={report.id}
                                    className="rounded-md border border-border p-3"
                                >
                                    <div className="mb-1 flex flex-wrap items-center justify-between gap-2">
                                        <p className="text-xs text-muted-foreground">
                                            {report.period_start} to {report.period_end}
                                        </p>
                                        <Badge variant="outline">{report.status}</Badge>
                                    </div>
                                    {report.status === 'completed' && report.payload && (
                                        <div className="space-y-2 text-sm text-secondary-foreground">
                                            <p>{report.payload.summary}</p>
                                            {report.payload.strengths &&
                                                report.payload.strengths.length > 0 && (
                                                    <p>
                                                        <span className="font-medium text-foreground">
                                                            Strengths:
                                                        </span>{' '}
                                                        {report.payload.strengths.join(', ')}
                                                    </p>
                                                )}
                                            {report.payload.focus_areas &&
                                                report.payload.focus_areas.length > 0 && (
                                                    <p>
                                                        <span className="font-medium text-foreground">
                                                            Focus areas:
                                                        </span>{' '}
                                                        {report.payload.focus_areas.join(', ')}
                                                    </p>
                                                )}
                                            {report.payload.engagement_note && (
                                                <p className="text-xs text-muted-foreground">
                                                    {report.payload.engagement_note}
                                                </p>
                                            )}
                                        </div>
                                    )}
                                    {report.status === 'failed' && report.error && (
                                        <p className="text-sm text-destructive">
                                            {report.error}
                                        </p>
                                    )}
                                    {report.status === 'pending' && (
                                        <p className="text-sm text-muted-foreground">
                                            Preparing report…
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            )}

            <section className="mb-6">
                <h2 className="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                    Conversations
                </h2>
                {conversations.length === 0 ? (
                    <p className="rounded-lg border border-border bg-card p-4 text-sm text-muted-foreground">
                        No conversations.
                    </p>
                ) : (
                    <ul className="divide-y divide-border overflow-hidden rounded-lg border border-border bg-card">
                        {conversations.map((conversation) => (
                            <li key={conversation.id}>
                                <Link
                                    href={`/staff/tutor/conversations/${conversation.id}`}
                                    className="flex items-center justify-between gap-3 px-4 py-3 hover:bg-accent"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-foreground">
                                            {conversation.title ??
                                                'Untitled conversation'}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {conversation.message_count} messages
                                        </p>
                                    </div>
                                    <span className="shrink-0 text-xs text-muted-foreground">
                                        {formatDateTime(
                                            conversation.last_activity_at,
                                        )}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <section className="mb-6">
                <h2 className="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                    Writing submissions
                </h2>
                {writingSubmissions.length === 0 ? (
                    <p className="rounded-lg border border-border bg-card p-4 text-sm text-muted-foreground">
                        No writing submissions.
                    </p>
                ) : (
                    <ul className="divide-y divide-border overflow-hidden rounded-lg border border-border bg-card">
                        {writingSubmissions.map((submission) => (
                            <li key={submission.id}>
                                <Link
                                    href={`/staff/tutor/writing/${submission.id}`}
                                    className="flex items-center justify-between gap-3 px-4 py-3 hover:bg-accent"
                                >
                                    <p className="min-w-0 truncate text-sm text-foreground">
                                        {submission.excerpt}
                                    </p>
                                    <span className="shrink-0 rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                                        {SUBMISSION_STATUS_LABELS[
                                            submission.status
                                        ] ?? submission.status}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <section>
                <h2 className="mb-2 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                    Needs attention
                </h2>
                {violations.length === 0 ? (
                    <p className="rounded-lg border border-border bg-card p-4 text-sm text-muted-foreground">
                        Nothing needs attention.
                    </p>
                ) : (
                    <ul className="divide-y divide-border overflow-hidden rounded-lg border border-border bg-card">
                        {violations.map((violation) => (
                            <li key={violation.id} className="px-4 py-3">
                                <div className="flex items-center justify-between gap-3">
                                    <span className="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">
                                        {violation.category_label}
                                    </span>
                                    <span className="text-xs text-muted-foreground">
                                        {formatDateTime(violation.occurred_at)}
                                    </span>
                                </div>
                                {violation.excerpt && (
                                    <p className="mt-1.5 text-sm text-secondary-foreground">
                                        &quot;{violation.excerpt}&quot;
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </GlcLayout>
    );
}
