<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\PlacementAiDraftStatus;
use App\Enums\Glc\PlacementSection;
use Carbon\CarbonInterface;
use Database\Factories\Glc\PlacementAiDraftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $placement_attempt_id
 * @property-read PlacementSection $section
 * @property-read array<string, int>|null $dimension_scores
 * @property-read string|null $transcript
 * @property-read string|null $feedback
 * @property-read string|null $confidence
 * @property-read PlacementAiDraftStatus $status
 * @property-read string|null $error
 * @property-read CarbonInterface|null $generated_at
 * @property-read PlacementAttempt $attempt
 */
final class PlacementAiDraft extends Model
{
    /** @use HasFactory<PlacementAiDraftFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'section' => PlacementSection::class,
            'dimension_scores' => 'array',
            'status' => PlacementAiDraftStatus::class,
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

    public function asPercentage(): ?float
    {
        $scores = $this->dimension_scores;

        if ($scores === null || $scores === []) {
            return null;
        }

        $average = array_sum($scores) / count($scores);

        return round(($average / 5) * 100, 2);
    }
}
