<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Models\User;
use Database\Factories\Glc\StudentAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $student_id
 * @property-read int $course_id
 * @property-read int $course_level_id
 * @property-read int $course_unit_id
 * @property-read int|null $assigned_by
 * @property-read User $student
 * @property-read Course $course
 * @property-read CourseLevel $level
 * @property-read CourseUnit $unit
 * @property-read User|null $assigner
 */
final class StudentAssignment extends Model
{
    /** @use HasFactory<StudentAssignmentFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<CourseLevel, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(CourseLevel::class, 'course_level_id');
    }

    /**
     * @return BelongsTo<CourseUnit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(CourseUnit::class, 'course_unit_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
