<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Models\User;
use Database\Factories\Glc\TutorUsageDailyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read string $date
 * @property-read int $active_minutes
 * @property-read int $message_count
 * @property-read int $conversation_starts
 * @property-read User $user
 */
final class TutorUsageDaily extends Model
{
    /** @use HasFactory<TutorUsageDailyFactory> */
    use HasFactory;

    protected $table = 'tutor_usage_daily';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'date' => 'date',
            'active_minutes' => 'integer',
            'message_count' => 'integer',
            'conversation_starts' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
