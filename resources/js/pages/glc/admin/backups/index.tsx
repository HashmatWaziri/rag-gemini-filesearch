import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import GlcLayout from '@/layouts/glc-layout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    Badge,
    buttonDangerClass,
    buttonPrimaryClass,
    buttonSecondaryClass,
    StatusBanner,
} from '../components';

interface BackupRow {
    path: string;
    name: string;
    size: number;
    size_label: string;
    date: string;
    age: string;
}

interface BackupHealth {
    count: number;
    newest: BackupRow | null;
    disk: string;
    scheduled: string;
}

interface BackupsIndexProps {
    backups: BackupRow[];
    health: BackupHealth;
    status?: string | null;
    error?: string | null;
}

function encodedPath(path: string): string {
    return path
        .split('/')
        .map((segment) => encodeURIComponent(segment))
        .join('/');
}

export default function BackupsIndex({
    backups,
    health,
    status,
    error,
}: BackupsIndexProps) {
    const [running, setRunning] = useState<'full' | 'db' | null>(null);
    const [restoringPath, setRestoringPath] = useState<string | null>(null);
    const [deletingPath, setDeletingPath] = useState<string | null>(null);
    const [confirmPath, setConfirmPath] = useState<string | null>(null);

    const runBackup = (databaseOnly: boolean) => {
        router.post(
            '/admin/backups',
            { database_only: databaseOnly },
            {
                preserveScroll: true,
                onStart: () => setRunning(databaseOnly ? 'db' : 'full'),
                onFinish: () => setRunning(null),
            },
        );
    };

    const restore = (path: string) => {
        if (confirmPath !== path) {
            return;
        }

        router.post(
            `/admin/backups/${encodedPath(path)}/restore`,
            { confirm: true },
            {
                preserveScroll: true,
                onStart: () => setRestoringPath(path),
                onFinish: () => {
                    setRestoringPath(null);
                    setConfirmPath(null);
                },
            },
        );
    };

    const destroy = (path: string) => {
        router.delete(`/admin/backups/${encodedPath(path)}`, {
            preserveScroll: true,
            onStart: () => setDeletingPath(path),
            onFinish: () => setDeletingPath(null),
        });
    };

    return (
        <GlcLayout title="Backups">
            <Head title="Backups" />

            <StatusBanner message={status} />
            {error && (
                <div className="mb-4 rounded-md border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                    {error}
                </div>
            )}

            <div className="space-y-6">
                <p className="text-sm text-secondary-foreground">
                    Manage Spatie Laravel Backup archives stored on the{' '}
                    <span className="font-medium text-mono">{health.disk}</span>{' '}
                    disk. {health.scheduled}.
                </p>

                <Card className="py-4">
                    <CardHeader>
                        <CardTitle className="text-base">Summary</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p>
                            <span className="text-muted-foreground">
                                Archives on disk:
                            </span>{' '}
                            <span className="font-semibold">{health.count}</span>
                        </p>
                        {health.newest && (
                            <p>
                                <span className="text-muted-foreground">
                                    Newest:
                                </span>{' '}
                                {health.newest.name}{' '}
                                <Badge tone="blue">{health.newest.age}</Badge>
                            </p>
                        )}
                    </CardContent>
                </Card>

                <div className="flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={() => runBackup(false)}
                        disabled={running !== null}
                        className={buttonPrimaryClass}
                    >
                        {running === 'full'
                            ? 'Running full backup…'
                            : 'Run full backup'}
                    </button>
                    <button
                        type="button"
                        onClick={() => runBackup(true)}
                        disabled={running !== null}
                        className={buttonSecondaryClass}
                    >
                        {running === 'db'
                            ? 'Running database backup…'
                            : 'Database only'}
                    </button>
                </div>

                <Card className="py-4">
                    <CardHeader>
                        <CardTitle className="text-base">
                            Backup archives
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {backups.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No backups yet. Run one above to get started.
                            </p>
                        ) : (
                            <ul className="divide-y divide-border rounded-md border border-border">
                                {backups.map((backup) => (
                                    <li
                                        key={backup.path}
                                        className="space-y-3 px-3 py-3"
                                    >
                                        <div className="flex flex-wrap items-start justify-between gap-2">
                                            <div>
                                                <p className="text-sm font-medium text-mono">
                                                    {backup.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {backup.size_label} ·{' '}
                                                    {backup.age}
                                                </p>
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                <a
                                                    href={`/admin/backups/download/${encodedPath(backup.path)}`}
                                                    className={buttonSecondaryClass}
                                                >
                                                    Download
                                                </a>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        destroy(backup.path)
                                                    }
                                                    disabled={
                                                        deletingPath ===
                                                        backup.path
                                                    }
                                                    className={buttonDangerClass}
                                                >
                                                    {deletingPath ===
                                                    backup.path
                                                        ? 'Deleting…'
                                                        : 'Delete'}
                                                </button>
                                            </div>
                                        </div>

                                        <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                            <label className="flex items-start gap-2">
                                                <input
                                                    type="checkbox"
                                                    checked={
                                                        confirmPath ===
                                                        backup.path
                                                    }
                                                    onChange={(e) =>
                                                        setConfirmPath(
                                                            e.target.checked
                                                                ? backup.path
                                                                : null,
                                                        )
                                                    }
                                                    className="mt-0.5 rounded border-input"
                                                />
                                                <span>
                                                    I understand restoring the
                                                    database will overwrite
                                                    current data.
                                                </span>
                                            </label>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    restore(backup.path)
                                                }
                                                disabled={
                                                    confirmPath !==
                                                        backup.path ||
                                                    restoringPath ===
                                                        backup.path
                                                }
                                                className={`${buttonDangerClass} mt-2`}
                                            >
                                                {restoringPath === backup.path
                                                    ? 'Restoring database…'
                                                    : 'Restore database only'}
                                            </button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </GlcLayout>
    );
}
