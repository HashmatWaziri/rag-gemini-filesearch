import { GlcDataTableCard } from '@/components/glc';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import GlcLayout from '@/layouts/glc-layout';
import { Head, router } from '@inertiajs/react';
import {
    Badge,
    Pagination,
    type Option,
    type Paginator,
} from '../components';

interface AuditEntry {
    id: number;
    action: string;
    action_label: string;
    actor_name: string | null;
    actor_email: string | null;
    subject: string | null;
    details: Record<string, unknown> | null;
    created_at: string;
}

interface AuditIndexProps {
    logs: Paginator<AuditEntry>;
    filters: { action: string | null };
    actions: Option[];
}

function detailValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '-';
    }

    if (typeof value === 'string') {
        return value;
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    if (Array.isArray(value)) {
        return value.map((entry) => detailValue(entry)).join(', ');
    }

    return JSON.stringify(value);
}

export default function AuditIndex({
    logs,
    filters,
    actions,
}: AuditIndexProps) {
    const applyFilter = (action: string) => {
        router.get(
            '/admin/audit',
            { action: action || undefined },
            { preserveState: true, replace: true },
        );
    };

    return (
        <GlcLayout title="Activity Log">
            <Head title="Activity Log" />

            <p className="-mt-2 mb-4 text-sm text-secondary-foreground">
                Who did what, and when. Every sensitive action is recorded here
                and cannot be edited or deleted.
            </p>

            <GlcDataTableCard
                filters={
                    <Select
                        value={filters.action ?? 'all'}
                        onValueChange={(value) =>
                            applyFilter(value === 'all' ? '' : value)
                        }
                    >
                        <SelectTrigger
                            className="w-56"
                            aria-label="Show only one kind of activity"
                        >
                            <SelectValue placeholder="All activity" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All activity</SelectItem>
                            {actions.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                }
                footer={<Pagination paginator={logs} />}
            >
                <Table>
                    <TableHeader>
                        <TableRow className="bg-muted/50 hover:bg-muted/50">
                            <TableHead className="text-xs uppercase">
                                When
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                Who
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                What they did
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                Affected record
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                Details
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {logs.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No activity recorded yet.
                                </TableCell>
                            </TableRow>
                        )}
                        {logs.data.map((entry) => (
                            <TableRow key={entry.id} className="align-top">
                                <TableCell className="whitespace-nowrap text-secondary-foreground">
                                    {new Date(
                                        entry.created_at,
                                    ).toLocaleString()}
                                </TableCell>
                                <TableCell>
                                    <p className="font-medium text-mono">
                                        {entry.actor_name ?? 'System'}
                                    </p>
                                    {entry.actor_email && (
                                        <p className="text-xs text-muted-foreground">
                                            {entry.actor_email}
                                        </p>
                                    )}
                                </TableCell>
                                <TableCell>
                                    <Badge tone="blue">
                                        {entry.action_label}
                                    </Badge>
                                </TableCell>
                                <TableCell className="text-secondary-foreground">
                                    {entry.subject ?? '-'}
                                </TableCell>
                                <TableCell>
                                    {entry.details ? (
                                        <dl className="max-w-md space-y-0.5 text-xs text-muted-foreground">
                                            {Object.entries(entry.details).map(
                                                ([key, value]) => (
                                                    <div
                                                        key={key}
                                                        className="flex gap-1"
                                                    >
                                                        <dt className="shrink-0 font-medium text-secondary-foreground">
                                                            {key.replaceAll(
                                                                '_',
                                                                ' ',
                                                            )}
                                                            :
                                                        </dt>
                                                        <dd className="break-all">
                                                            {detailValue(value)}
                                                        </dd>
                                                    </div>
                                                ),
                                            )}
                                        </dl>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            -
                                        </span>
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </GlcDataTableCard>
        </GlcLayout>
    );
}
