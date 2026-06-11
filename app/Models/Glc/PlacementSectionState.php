<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use Carbon\CarbonInterface;
use Database\Factories\Glc\PlacementSectionStateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $placement_attempt_id
 * @property-read PlacementSection $section
 * @property-read PlacementSectionStatus $status
 * @property-read int $time_limit_seconds
 * @property-read int $time_used_seconds
 * @property-read CarbonInterface|null $started_at
 * @property-read CarbonInterface|null $completed_at
 * @property-read CarbonInterface|null $last_resumed_at
 * @property-read CarbonInterface|null $paused_at
 * @property-read PlacementAttempt $attempt
 */
final class PlacementSectionState extends Model
{
    /** @use HasFactory<PlacementSectionStateFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'section' => PlacementSection::class,
            'status' => PlacementSectionStatus::class,
            'time_limit_seconds' => 'integer',
            'time_used_seconds' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_resumed_at' => 'datetime',
            'paused_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PlacementAttempt, $this>
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PlacementAttempt::class, 'placement_attempt_id');
    }

    public function remainingSeconds(): int
    {
        return max(0, $this->time_limit_seconds - $this->time_used_seconds);
    }

    public function isTimeExpired(): bool
    {
        return $this->remainingSeconds() === 0;
    }
}
