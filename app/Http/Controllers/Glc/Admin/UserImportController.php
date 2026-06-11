<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Services\Glc\Admin\UserCsvImporter;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final readonly class UserImportController
{
    public function __construct(
        private UserCsvImporter $importer,
        private AuditLogger $auditLogger,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = $this->importer->import($request->file('file'));

        $this->auditLogger->log(AuditAction::UsersBulkImported, $request->user(), null, [
            'created' => $result['created'],
            'failed' => count($result['errors']),
        ]);

        return to_route('admin.users.index')
            ->with('glc_import_result', $result)
            ->with('glc_status', $this->summary($result['created'], count($result['errors'])));
    }

    private function summary(int $created, int $failed): string
    {
        $message = sprintf('Import finished: %d %s added.', $created, Str::plural('user', $created));

        if ($failed > 0) {
            $message .= sprintf(
                ' %d %s could not be added — the reasons are listed below.',
                $failed,
                Str::plural('row', $failed),
            );
        }

        return $message;
    }
}
