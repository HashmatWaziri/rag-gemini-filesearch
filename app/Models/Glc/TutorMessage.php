<?php

declare(strict_types=1);

namespace App\Models\Glc;

use Database\Factories\Glc\TutorMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $tutor_conversation_id
 * @property-read string $role
 * @property-read string $content
 * @property-read array<string, mixed>|null $metadata
 * @property-read TutorConversation $conversation
 */
final class TutorMessage extends Model
{
    /** @use HasFactory<TutorMessageFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<TutorConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(TutorConversation::class, 'tutor_conversation_id');
    }
}
