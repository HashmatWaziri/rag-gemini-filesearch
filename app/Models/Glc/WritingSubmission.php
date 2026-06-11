<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Models\User;
use Database\Factories\Glc\WritingSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int|null $tutor_conversation_id
 * @property-read string $text
 * @property-read array<string, mixed>|null $feedback
 * @property-read list<array<string, mixed>>|null $highlights
 * @property-read string $status
 * @property-read string|null $error
 * @property-read User $user
 * @property-read TutorConversation|null $conversation
 */
final class WritingSubmission extends Model
{
    /** @use HasFactory<WritingSubmissionFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'feedback' => 'array',
            'highlights' => 'array',
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
