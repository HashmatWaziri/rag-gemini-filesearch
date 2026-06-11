<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\PlacementIntegrityEventType;
use Carbon\CarbonInterface;
use Database\Factories\Glc\PlacementIntegrityEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $placement_attempt_id
 * @property-read PlacementIntegrityEventType $type
 * @property-read array<string, mixed>|null $metadata
 * @property-read CarbonInterface $occurred_at
 * @property-read PlacementAttempt $attempt
 */
final class PlacementIntegrityEvent extends Model
{
    /** @use HasFactory<PlacementIntegrityEventFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'type' => PlacementIntegrityEventType::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PlacementAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PlacementAttempt::class, 'placement_attempt_id');
    }
}
