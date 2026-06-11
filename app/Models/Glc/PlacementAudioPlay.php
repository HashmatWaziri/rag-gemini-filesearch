<?php

declare(strict_types=1);

namespace App\Models\Glc;

use Carbon\CarbonInterface;
use Database\Factories\Glc\PlacementAudioPlayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $placement_attempt_id
 * @property-read int $placement_item_id
 * @property-read CarbonInterface $played_at
 * @property-read PlacementAttempt $attempt
 * @property-read PlacementItem $item
 */
final class PlacementAudioPlay extends Model
{
    /** @use HasFactory<PlacementAudioPlayFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'played_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PlacementAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PlacementAttempt::class, 'placement_attempt_id');
    }

    /**
     * @return BelongsTo<PlacementItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(PlacementItem::class, 'placement_item_id');
    }
}
