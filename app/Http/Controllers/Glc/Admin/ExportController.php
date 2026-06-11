<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\CurriculumDocumentStatus;
use App\Models\Glc\AuditLog;
use App\Services\Glc\Admin\DataExporter;
use App\Services\Glc\Admin\ExportBundle;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'curriculumStatuses' => array_map(fn (CurriculumDocumentStatus $status): array => [
                'value' => $status->value,
                'label' => $this->curriculumStatusLabel($status),
            ], CurriculumDocumentStatus::cases()),
            'recentExports' => $recentExports,
        ]);
    }

    public function download(Request $request, string $bundle): BinaryFileResponse
    {
        $type = ExportBundle::tryFrom($bundle);

        abort_unless($type instanceof ExportBundle, 404);

        $statuses = $this->curriculumStatuses($request, $type);

        $path = $this->exporter->build($type, $statuses);

        $details = ['bundle' => $type->value];

        if ($statuses !== null) {
            $details['statuses'] = array_map(
                fn (CurriculumDocumentStatus $status): string => $status->value,
                $statuses,
            );
        }

        $this->auditLogger->log(AuditAction::DataExported, $request->user(), null, $details);

        return response()
            ->download($path, $type->fileName(), ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    /**
     * @return list<CurriculumDocumentStatus>|null
     */
    private function curriculumStatuses(Request $request, ExportBundle $type): ?array
    {
        if ($type !== ExportBundle::Curriculum || ! $request->has('statuses')) {
            return null;
        }

        $validated = $request->validate([
            'statuses' => ['required', 'array', 'min:1'],
            'statuses.*' => ['string', Rule::enum(CurriculumDocumentStatus::class)],
        ]);

        return array_values(array_map(
            fn (string $value): CurriculumDocumentStatus => CurriculumDocumentStatus::from($value),
            array_unique($validated['statuses']),
        ));
    }

    private function curriculumStatusLabel(CurriculumDocumentStatus $status): string
    {
        return match ($status) {
            CurriculumDocumentStatus::Draft => 'Draft — not yet available to the AI Tutor',
            CurriculumDocumentStatus::Published => 'Published — available to the AI Tutor',
            CurriculumDocumentStatus::Archived => 'Archived — removed from the AI Tutor',
        };
    }
}
