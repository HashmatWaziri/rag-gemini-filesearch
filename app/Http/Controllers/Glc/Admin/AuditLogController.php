<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Services\Glc\Admin\AuditActionLabels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AuditLogController
{
    public function index(Request $request): Response
    {
        $action = AuditAction::tryFrom((string) $request->query('action'));

        $logs = AuditLog::query()
            ->with('actor:id,name,email')
            ->when($action instanceof AuditAction, fn (Builder $query) => $query->where('action', $action))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action->value,
                'action_label' => AuditActionLabels::for($log->action),
                'actor_name' => $log->actor?->name,
                'actor_email' => $log->actor?->email,
                'subject' => $log->subject_type === null
                    ? null
                    : str(class_basename($log->subject_type))->headline()->toString().' #'.$log->subject_id,
                'details' => $log->details,
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        return Inertia::render('glc/admin/audit/index', [
            'logs' => $logs,
            'filters' => ['action' => $action?->value],
            'actions' => array_map(fn (AuditAction $case): array => [
                'value' => $case->value,
                'label' => AuditActionLabels::for($case),
            ], AuditAction::cases()),
        ]);
    }
}
