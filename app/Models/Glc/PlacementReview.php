<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementReviewStatus;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Glc\PlacementReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read int $placement_attempt_id
 * @property-read int|null $assigned_to
 * @property-read PlacementReviewStatus $status
 * @property-read GlcLevel|null $final_level
 * @property-read array<string, string>|null $skill_levels
 * @property-read string|null $override_reason
 * @property-read int|null $overridden_by
 * @property-read array<string, string>|null $narrative
 * @property-read CarbonInterface|null $narrative_approved_at
 * @property-read int|null $narrative_approved_by
 * @property-read list<string>|null $flags
 * @property-read CarbonInterface|null $approved_at
 * @property-read int|null $approved_by
 * @property-read PlacementAttempt $attempt
 * @property-read User|null $assignee
 * @property-read User|null $approver
 * @property-read \Illuminate\Support\Collection<int, PlacementReviewNote> $notes
 */
final class PlacementReview extends Model
{
    /** @use HasFactory<PlacementReviewFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'status' => PlacementReviewStatus::class,
            'final_level' => GlcLevel::class,
            'skill_levels' => 'array',
            'narrative' => 'array',
            'narrative_approved_at' => 'datetime',
            'flags' => 'array',
            'approved_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<PlacementReviewNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(PlacementReviewNote::class);
    }

    public function hasFlag(string $flag): bool
    {
        return in_array($flag, $this->flags ?? [], true);
    }

    public function isNarrativeApproved(): bool
    {
        return $this->narrative_approved_at !== null;
    }

    public function canGeneratePdf(): bool
    {
        return $this->isNarrativeApproved()
            && ($this->status === PlacementReviewStatus::Approved || $this->status === PlacementReviewStatus::Sent);
    }
}
