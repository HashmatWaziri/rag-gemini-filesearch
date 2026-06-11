<?php

declare(strict_types=1);

namespace App\Models\Glc;

use Database\Factories\Glc\CourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read \Illuminate\Support\Collection<int, CourseLevel> $levels
 * @property-read \Illuminate\Support\Collection<int, CurriculumDocument> $documents
 */
final class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return HasMany<CourseLevel, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(CourseLevel::class)->orderBy('position');
    }

    /**
     * @return HasMany<CurriculumDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CurriculumDocument::class);
    }
}
