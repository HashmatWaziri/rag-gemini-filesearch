<?php

declare(strict_types=1);

namespace App\Models\Glc;

use Database\Factories\Glc\CourseUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read int $course_level_id
 * @property-read string $name
 * @property-read int $position
 * @property-read CourseLevel $level
 * @property-read \Illuminate\Support\Collection<int, CourseLesson> $lessons
 */
final class CourseUnit extends Model
{
    /** @use HasFactory<CourseUnitFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<CourseLevel, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(CourseLevel::class, 'course_level_id');
    }

    /**
     * @return HasMany<CourseLesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class)->orderBy('position');
    }
}
