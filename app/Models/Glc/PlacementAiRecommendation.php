<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementAiDraftStatus;
use Carbon\CarbonInterface;
use Database\Factories\Glc\PlacementAiRecommendationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $placement_attempt_id
 * @property-read PlacementAiDraftStatus $status
 * @property-read GlcLevel|null $recommended_level
 * @property-read array<string, string>|null $skill_levels
 * @property-read array<string, string>|null $skill_summaries
 * @property-read string|null $confidence
 * @property-read string|null $rationale
 * @property-read string|null $error
 * @property-read CarbonInterface|null $generated_at
 * @property-read PlacementAttempt $attempt
 */
final class PlacementAiRecommendation extends Model
{
    /** @use HasFactory<PlacementAiRecommendationFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'status' => PlacementAiDraftStatus::class,
            'recommended_level' => GlcLevel::class,
            'skill_levels' => 'array',
            'skill_summaries' => 'array',
            'generated_at' => 'datetime',
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
