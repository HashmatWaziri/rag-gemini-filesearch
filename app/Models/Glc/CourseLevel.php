<?php

declare(strict_types=1);

namespace App\Models\Glc;

use Database\Factories\Glc\CourseLevelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read int $course_id
 * @property-read string $name
 * @property-read int $position
 * @property-read Course $course
 * @property-read \Illuminate\Support\Collection<int, CourseUnit> $units
 */
final class CourseLevel extends Model
{
    /** @use HasFactory<CourseLevelFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<CourseUnit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(CourseUnit::class)->orderBy('position');
    }
}
