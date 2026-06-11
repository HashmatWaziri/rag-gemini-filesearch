<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Glc\PlacementResultLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property-read int $id
 * @property-read int $placement_attempt_id
 * @property-read string $token
 * @property-read string $email_to
 * @property-read CarbonInterface $expires_at
 * @property-read CarbonInterface|null $sent_at
 * @property-read int|null $sent_by
 * @property-read CarbonInterface|null $last_viewed_at
 * @property-read PlacementAttempt $attempt
 * @property-read User|null $sender
 */
final class PlacementResultLink extends Model
{
    /** @use HasFactory<PlacementResultLinkFactory> */
    use HasFactory;

    protected $guarded = [];

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'last_viewed_at' => 'datetime',
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
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
