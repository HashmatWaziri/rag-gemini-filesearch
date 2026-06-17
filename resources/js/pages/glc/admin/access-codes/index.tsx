import { GlcDataTableCard, GlcSearchInput } from '@/components/glc';
import { MetronicSelect } from '@/components/glc/metronic-select';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import GlcLayout from '@/layouts/glc-layout';
import { Head, router, useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import {
    Badge,
    ConfirmDialog,
    Field,
    Modal,
    type Option,
    Pagination,
    type Paginator,
    StatusBanner,
} from '../components';
import { Input } from '@/components/ui/input';

interface AccessCode {
    id: number;
    code: string;
    status: string;
    status_label: string;
    is_expired: boolean;
    expires_at: string | null;
    revoked_at: string | null;
    note: string | null;
    issuer_name: string | null;
    attempts_count: number;
    can_revoke: boolean;
    created_at: string;
}

interface AccessCodesIndexProps {
    codes: Paginator<AccessCode>;
    filters: { status: string | null; search: string };
    statuses: Option[];
    status?: string | null;
}

function statusTone(status: string): 'green' | 'blue' | 'slate' | 'red' {
    switch (status) {
        case 'unused':
            return 'green';
        case 'in_progress':
            return 'blue';
        case 'completed':
            return 'slate';
        default:
            return 'red';
    }
}

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '-';
}

export default function AccessCodesIndex({
    codes,
    filters,
    statuses,
    status,
}: AccessCodesIndexProps) {
    const [createOpen, setCreateOpen] = useState(false);
    const [revoking, setRevoking] = useState<AccessCode | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');

    const createForm = useForm({
        quantity: '1',
        expires_at: '',
        note: '',
    });

    const applyFilters = (overrides: Partial<{ status: string }> = {}) => {
        router.get(
            '/admin/access-codes',
            {
                search: search || undefined,
                status: overrides.status ?? filters.status ?? undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const submitCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.post('/admin/access-codes', {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                setCreateOpen(false);
            },
        });
    };

    const confirmRevoke = () => {
        if (!revoking) {
            return;
        }

        router.patch(
            `/admin/access-codes/${revoking.id}/revoke`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setRevoking(null),
            },
        );
    };

    return (
        <GlcLayout title="Access Codes">
            <Head title="Access Codes" />

            <p className="-mt-2 mb-4 text-sm text-secondary-foreground">
                Single-use codes that let one candidate take the placement test.
                Share a code with a candidate to invite them.
            </p>

            <StatusBanner message={status} />

            <GlcDataTableCard
                filters={
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyFilters();
                        }}
                        className="flex flex-wrap items-center gap-2.5"
                    >
                        <GlcSearchInput
                            value={search}
                            onValueChange={setSearch}
                            placeholder="Search code"
                            aria-label="Search codes"
                            inputClassName="sm:w-52"
                        />
                        <MetronicSelect
                            className="w-40"
                            aria-label="Filter by status"
                            value={filters.status}
                            onChange={(value) =>
                                applyFilters({ status: value ?? undefined })
                            }
                            options={statuses.map((option) => ({
                                value: option.value,
                                label: option.label,
                            }))}
                            placeholder="All statuses"
                            isSearchable={false}
                        />
                        <Button type="submit" variant="outline">
                            Search
                        </Button>
                    </form>
                }
                actions={
                    <Button type="button" onClick={() => setCreateOpen(true)}>
                        New codes
                    </Button>
                }
                footer={<Pagination paginator={codes} />}
            >
                <Table>
                    <TableHeader>
                        <TableRow className="bg-muted/50 hover:bg-muted/50">
                            <TableHead className="text-xs uppercase">
                                Code
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                Status
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                Expires
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                Note
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                Issued by
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                Attempts
                            </TableHead>
                            <TableHead className="text-xs uppercase">
                                <span className="sr-only">Actions</span>
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {codes.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={7}
                                    className="py-8 text-center text-muted-foreground"
                                >
                                    No access codes found.
                                </TableCell>
                            </TableRow>
                        )}
                        {codes.data.map((code) => (
                            <TableRow key={code.id}>
                                <TableCell className="font-mono text-sm font-semibold tracking-wide text-mono">
                                    {code.code}
                                </TableCell>
                                <TableCell>
                                    <div className="flex flex-wrap gap-1">
                                        <Badge tone={statusTone(code.status)}>
                                            {code.status_label}
                                        </Badge>
                                        {code.is_expired &&
                                            code.status === 'unused' && (
                                                <Badge tone="red">
                                                    Expired
                                                </Badge>
                                            )}
                                    </div>
                                </TableCell>
                                <TableCell className="text-secondary-foreground">
                                    {formatDate(code.expires_at)}
                                </TableCell>
                                <TableCell className="max-w-40 truncate text-secondary-foreground">
                                    {code.note ?? '-'}
                                </TableCell>
                                <TableCell className="text-secondary-foreground">
                                    {code.issuer_name ?? '-'}
                                </TableCell>
                                <TableCell className="text-secondary-foreground">
                                    {code.attempts_count}
                                </TableCell>
                                <TableCell className="text-right">
                                    {code.can_revoke && (
                                        <button
                                            type="button"
                                            onClick={() => setRevoking(code)}
                                            className="text-sm font-medium text-destructive hover:underline"
                                        >
                                            Revoke
                                        </button>
                                    )}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </GlcDataTableCard>

            <Modal
                open={createOpen}
                title="Create access codes"
                onClose={() => setCreateOpen(false)}
            >
                <form onSubmit={submitCreate} className="space-y-4">
                    <Field
                        label="Quantity"
                        htmlFor="quantity"
                        error={createForm.errors.quantity}
                        hint="Create 1-100 codes in one batch."
                    >
                        <Input
                            id="quantity"
                            type="number"
                            min={1}
                            max={100}
                            value={createForm.data.quantity}
                            onChange={(e) =>
                                createForm.setData('quantity', e.target.value)
                            }
                            required
                        />
                    </Field>

                    <Field
                        label="Expires at (optional)"
                        htmlFor="expires_at"
                        error={createForm.errors.expires_at}
                    >
                        <Input
                            id="expires_at"
                            type="datetime-local"
                            value={createForm.data.expires_at}
                            onChange={(e) =>
                                createForm.setData('expires_at', e.target.value)
                            }
                        />
                    </Field>

                    <Field
                        label="Note (optional)"
                        htmlFor="note"
                        error={createForm.errors.note}
                        hint="Internal note, e.g. intake batch or candidate name."
                    >
                        <Input
                            id="note"
                            type="text"
                            maxLength={255}
                            value={createForm.data.note}
                            onChange={(e) =>
                                createForm.setData('note', e.target.value)
                            }
                        />
                    </Field>

                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setCreateOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={createForm.processing}
                        >
                            Create
                        </Button>
                    </div>
                </form>
            </Modal>

            <ConfirmDialog
                open={revoking !== null}
                title="Revoke access code"
                message={`Revoke code ${revoking?.code ?? ''}? The candidate holding it will no longer be able to start or continue the placement test, and the code cannot be brought back. If they still need to take the test, create a new code for them.`}
                confirmLabel="Revoke"
                danger
                onConfirm={confirmRevoke}
                onCancel={() => setRevoking(null)}
            />
        </GlcLayout>
    );
}
