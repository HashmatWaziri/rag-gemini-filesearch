<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\GlcLevel;
use Carbon\CarbonInterface;
use Database\Factories\Glc\PlacementScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $placement_attempt_id
 * @property-read array<string, float|null>|null $section_scores
 * @property-read string|null $composite
 * @property-read GlcLevel|null $suggested_level
 * @property-read bool $variance_flagged
 * @property-read CarbonInterface|null $computed_at
 * @property-read PlacementAttempt $attempt
 */
final class PlacementScore extends Model
{
    /** @use HasFactory<PlacementScoreFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'section_scores' => 'array',
            'composite' => 'decimal:2',
            'suggested_level' => GlcLevel::class,
            'variance_flagged' => 'boolean',
            'computed_at' => 'datetime',
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
