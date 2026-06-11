<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\PlacementAttemptStatus;
use App\Enums\Glc\PlacementSection;
use Carbon\CarbonInterface;
use Database\Factories\Glc\PlacementAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read int $id
 * @property-read int $placement_access_code_id
 * @property-read string $candidate_name
 * @property-read string $candidate_email
 * @property-read int $candidate_age
 * @property-read PlacementAttemptStatus $status
 * @property-read string $device_token
 * @property-read PlacementSection|null $current_section
 * @property-read CarbonInterface|null $privacy_acknowledged_at
 * @property-read CarbonInterface|null $instructions_acknowledged_at
 * @property-read CarbonInterface|null $last_activity_at
 * @property-read CarbonInterface|null $started_at
 * @property-read CarbonInterface|null $submitted_at
 * @property-read CarbonInterface|null $terminated_at
 * @property-read string|null $termination_reason
 * @property-read PlacementAccessCode $accessCode
 * @property-read \Illuminate\Support\Collection<int, PlacementSectionState> $sectionStates
 * @property-read \Illuminate\Support\Collection<int, PlacementAnswer> $answers
 * @property-read \Illuminate\Support\Collection<int, PlacementAudioPlay> $audioPlays
 * @property-read \Illuminate\Support\Collection<int, PlacementIntegrityEvent> $integrityEvents
 * @property-read PlacementScore|null $score
 * @property-read \Illuminate\Support\Collection<int, PlacementAiDraft> $aiDrafts
 * @property-read PlacementAiRecommendation|null $aiRecommendation
 * @property-read PlacementReview|null $review
 * @property-read \Illuminate\Support\Collection<int, PlacementResultLink> $resultLinks
 */
final class PlacementAttempt extends Model
{
    /** @use HasFactory<PlacementAttemptFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'status' => PlacementAttemptStatus::class,
            'current_section' => PlacementSection::class,
            'candidate_age' => 'integer',
            'privacy_acknowledged_at' => 'datetime',
            'instructions_acknowledged_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'terminated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PlacementAccessCode, $this>
     */
    public function accessCode(): BelongsTo
    {
        return $this->belongsTo(PlacementAccessCode::class, 'placement_access_code_id');
    }

    /**
     * @return HasMany<PlacementSectionState, $this>
     */
    public function sectionStates(): HasMany
    {
        return $this->hasMany(PlacementSectionState::class);
    }

    /**
     * @return HasMany<PlacementAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(PlacementAnswer::class);
    }

    /**
     * @return HasMany<PlacementAudioPlay, $this>
     */
    public function audioPlays(): HasMany
    {
        return $this->hasMany(PlacementAudioPlay::class);
    }

    /**
     * @return HasMany<PlacementIntegrityEvent, $this>
     */
    public function integrityEvents(): HasMany
    {
        return $this->hasMany(PlacementIntegrityEvent::class);
    }

    /**
     * @return HasOne<PlacementScore, $this>
     */
    public function score(): HasOne
    {
        return $this->hasOne(PlacementScore::class);
    }

    /**
     * @return HasMany<PlacementAiDraft, $this>
     */
    public function aiDrafts(): HasMany
    {
        return $this->hasMany(PlacementAiDraft::class);
    }

    /**
     * @return HasOne<PlacementAiRecommendation, $this>
     */
    public function aiRecommendation(): HasOne
    {
        return $this->hasOne(PlacementAiRecommendation::class);
    }

    /**
     * @return HasOne<PlacementReview, $this>
     */
    public function review(): HasOne
    {
        return $this->hasOne(PlacementReview::class);
    }

    /**
     * @return HasMany<PlacementResultLink, $this>
     */
    public function resultLinks(): HasMany
    {
        return $this->hasMany(PlacementResultLink::class);
    }

    public function isMinor(): bool
    {
        return $this->candidate_age >= config()->integer('glc.guardian_consent.min_age', 12)
            && $this->candidate_age <= config()->integer('glc.guardian_consent.max_age', 17);
    }

    public function isWithinResumeWindow(): bool
    {
        if (! $this->started_at instanceof CarbonInterface) {
            return true;
        }

        return $this->started_at->addHours(
            config()->integer('glc.placement.resume_window_hours', 24)
        )->isFuture();
    }
}
