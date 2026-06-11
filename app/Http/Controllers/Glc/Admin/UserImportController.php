<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Services\Glc\Admin\UserCsvImporter;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            ->with('glc_status', sprintf(
                'Bulk import finished: %d user(s) created, %d row(s) failed.',
                $result['created'],
                count($result['errors']),
            ));
    }
}
