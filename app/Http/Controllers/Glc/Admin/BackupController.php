<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Services\Glc\Admin\BackupManager;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

final readonly class BackupController
{
    public function __construct(
        private BackupManager $backups,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('glc/admin/backups/index', [
            'backups' => $this->backups->list(),
            'health' => $this->backups->healthSummary(),
            'status' => $request->session()->get('glc_status'),
            'error' => $request->session()->get('glc_error'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $databaseOnly = $request->boolean('database_only');

        try {
            $this->backups->run($databaseOnly);

            $this->auditLogger->log(
                AuditAction::BackupCreated,
                $request->user(),
                null,
                ['database_only' => $databaseOnly],
            );

            return to_route('admin.backups.index')->with(
                'glc_status',
                $databaseOnly ? 'Database backup completed.' : 'Full backup completed.',
            );
        } catch (Throwable $exception) {
            return to_route('admin.backups.index')->with(
                'glc_error',
                'Backup failed: '.$exception->getMessage(),
            );
        }
    }

    public function download(Request $request, string $path): BinaryFileResponse|RedirectResponse
    {
        try {
            $absolute = $this->backups->absolutePath($path);

            $this->auditLogger->log(AuditAction::BackupDownloaded, $request->user(), null, [
                'path' => $path,
            ]);

            return response()->download($absolute, basename($path));
        } catch (Throwable $exception) {
            return to_route('admin.backups.index')->with(
                'glc_error',
                'Download failed: '.$exception->getMessage(),
            );
        }
    }

    public function destroy(Request $request, string $path): RedirectResponse
    {
        try {
            $this->backups->delete($path);

            $this->auditLogger->log(AuditAction::BackupDeleted, $request->user(), null, [
                'path' => $path,
            ]);

            return to_route('admin.backups.index')->with('glc_status', 'Backup deleted.');
        } catch (Throwable $exception) {
            return to_route('admin.backups.index')->with(
                'glc_error',
                'Delete failed: '.$exception->getMessage(),
            );
        }
    }

    public function restore(Request $request, string $path): RedirectResponse
    {
        $request->validate([
            'confirm' => ['required', 'accepted'],
        ]);

        try {
            DB::disconnect();

            $this->backups->restoreDatabase($path);

            $this->auditLogger->log(AuditAction::BackupRestored, $request->user(), null, [
                'path' => $path,
                'scope' => 'database',
            ]);

            return to_route('admin.backups.index')->with(
                'glc_status',
                'Database restored from backup. Verify application data before continuing.',
            );
        } catch (Throwable $exception) {
            return to_route('admin.backups.index')->with(
                'glc_error',
                'Restore failed: '.$exception->getMessage(),
            );
        }
    }
}
