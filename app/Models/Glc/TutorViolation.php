<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\TutorViolationCategory;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Glc\TutorViolationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int|null $tutor_conversation_id
 * @property-read int|null $tutor_message_id
 * @property-read TutorViolationCategory $category
 * @property-read string|null $excerpt
 * @property-read CarbonInterface $occurred_at
 * @property-read User $user
 * @property-read TutorConversation|null $conversation
 */
final class TutorViolation extends Model
{
    /** @use HasFactory<TutorViolationFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'category' => TutorViolationCategory::class,
            'occurred_at' => 'datetime',
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
     * @return BelongsTo<TutorConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TutorConversation::class, 'tutor_conversation_id');
    }
}
