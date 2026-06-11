<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Glc\TutorConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read string|null $title
 * @property-read string|null $summary
 * @property-read CarbonInterface|null $last_activity_at
 * @property-read User $user
 * @property-read \Illuminate\Support\Collection<int, TutorMessage> $messages
 */
final class TutorConversation extends Model
{
    /** @use HasFactory<TutorConversationFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<TutorMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TutorMessage::class);
    }

    public function messagePairCount(): int
    {
        return (int) floor($this->messages()->count() / 2);
    }
}
