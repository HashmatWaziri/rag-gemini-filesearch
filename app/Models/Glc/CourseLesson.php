<?php

declare(strict_types=1);

namespace App\Models\Glc;

use Database\Factories\Glc\CourseLessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $course_unit_id
 * @property-read string $name
 * @property-read int $position
 * @property-read CourseUnit $unit
 */
final class CourseLesson extends Model
{
    /** @use HasFactory<CourseLessonFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<CourseUnit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(CourseUnit::class, 'course_unit_id');
    }
}
