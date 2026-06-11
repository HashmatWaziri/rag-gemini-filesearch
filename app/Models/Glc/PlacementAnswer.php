<?php

declare(strict_types=1);

namespace App\Models\Glc;

use Database\Factories\Glc\PlacementAnswerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $placement_attempt_id
 * @property-read int $placement_item_id
 * @property-read array<string, mixed>|null $response
 * @property-read bool|null $is_correct
 * @property-read int|null $word_count
 * @property-read int $recording_attempts
 * @property-read PlacementAttempt $attempt
 * @property-read PlacementItem $item
 */
final class PlacementAnswer extends Model
{
    /** @use HasFactory<PlacementAnswerFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'response' => 'array',
            'is_correct' => 'boolean',
            'word_count' => 'integer',
            'recording_attempts' => 'integer',
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
