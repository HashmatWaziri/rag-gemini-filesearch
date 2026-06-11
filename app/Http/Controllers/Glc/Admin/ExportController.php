<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Services\Glc\Admin\DataExporter;
use App\Services\Glc\Admin\ExportBundle;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class ExportController
{
    public function __construct(
        private DataExporter $exporter,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        $recentExports = AuditLog::query()
            ->with('actor:id,name')
            ->where('action', AuditAction::DataExported)
            ->latest('created_at')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'bundle' => $log->details['bundle'] ?? null,
                'actor_name' => $log->actor?->name,
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        return Inertia::render('glc/admin/exports/index', [
            'bundles' => array_map(fn (ExportBundle $bundle): array => [
                'value' => $bundle->value,
                'label' => $bundle->label(),
                'description' => $bundle->description(),
                'contents' => $bundle->contents(),
            ], ExportBundle::cases()),
            'recentExports' => $recentExports,
        ]);
    }

    public function download(Request $request, string $bundle): BinaryFileResponse
    {
        $type = ExportBundle::tryFrom($bundle);

        abort_unless($type instanceof ExportBundle, 404);

        $path = $this->exporter->build($type);

        $this->auditLogger->log(AuditAction::DataExported, $request->user(), null, [
            'bundle' => $type->value,
        ]);

        return response()
            ->download($path, $type->fileName(), ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }
}
