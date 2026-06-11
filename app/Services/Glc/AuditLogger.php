<?php

declare(strict_types=1);

namespace App\Services\Glc;

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function log(AuditAction $action, ?User $actor, ?Model $subject = null, array $details = []): AuditLog
    {
        return AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject instanceof Model ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'details' => $details === [] ? null : $details,
            'created_at' => now(),
        ]);
    }
}
