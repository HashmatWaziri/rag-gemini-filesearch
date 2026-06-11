<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\AuditAction;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Glc\AuditLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int|null $actor_id
 * @property-read AuditAction $action
 * @property-read string|null $subject_type
 * @property-read int|null $subject_id
 * @property-read array<string, mixed>|null $details
 * @property-read CarbonInterface $created_at
 * @property-read User|null $actor
 */
final class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    public const null UPDATED_AT = null;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
