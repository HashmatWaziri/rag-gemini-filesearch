<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use Database\Factories\Glc\PlacementItemFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read PlacementSection $section
 * @property-read PlacementItemType $type
 * @property-read int|null $parent_id
 * @property-read int $position
 * @property-read string|null $title
 * @property-read string|null $body
 * @property-read list<string>|null $options
 * @property-read int|null $correct_option
 * @property-read string|null $media_path
 * @property-read array<string, mixed>|null $settings
 * @property-read bool $is_active
 * @property-read PlacementItem|null $parent
 * @property-read \Illuminate\Support\Collection<int, PlacementItem> $children
 */
final class PlacementItem extends Model
{
    /** @use HasFactory<PlacementItemFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'section' => PlacementSection::class,
            'type' => PlacementItemType::class,
            'options' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<PlacementItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<PlacementItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function isScoreable(): bool
    {
        return $this->type === PlacementItemType::Question && $this->correct_option !== null;
    }

    /**
     * @param  Builder<PlacementItem>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<PlacementItem>  $query
     */
    #[Scope]
    protected function forSection(Builder $query, PlacementSection $section): void
    {
        $query->where('section', $section);
    }
}
