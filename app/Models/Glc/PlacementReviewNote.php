<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Models\User;
use Database\Factories\Glc\PlacementReviewNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $placement_review_id
 * @property-read int|null $author_id
 * @property-read string $note
 * @property-read PlacementReview $review
 * @property-read User|null $author
 */
final class PlacementReviewNote extends Model
{
    /** @use HasFactory<PlacementReviewNoteFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<PlacementReview, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(PlacementReview::class, 'placement_review_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
