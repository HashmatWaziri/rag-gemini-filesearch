<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\PlacementAccessCodeStatus;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Glc\PlacementAccessCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property-read int $id
 * @property-read string $code
 * @property-read PlacementAccessCodeStatus $status
 * @property-read CarbonInterface|null $expires_at
 * @property-read int|null $issued_by
 * @property-read CarbonInterface|null $revoked_at
 * @property-read string|null $note
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User|null $issuer
 * @property-read \Illuminate\Support\Collection<int, PlacementAttempt> $attempts
 */
final class PlacementAccessCode extends Model
{
    /** @use HasFactory<PlacementAccessCodeFactory> */
    use HasFactory;

    protected $guarded = [];

    public static function generateCode(): string
    {
        return mb_strtoupper(Str::random(8));
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'status' => PlacementAccessCodeStatus::class,
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @return HasMany<PlacementAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(PlacementAttempt::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at instanceof CarbonInterface && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->status === PlacementAccessCodeStatus::Unused && ! $this->isExpired();
    }
}
