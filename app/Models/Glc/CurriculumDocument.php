<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Glc\CurriculumDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $course_id
 * @property-read int $course_level_id
 * @property-read int $course_unit_id
 * @property-read int|null $course_lesson_id
 * @property-read string $title
 * @property-read string $original_filename
 * @property-read string $file_path
 * @property-read string $format
 * @property-read string|null $extracted_text
 * @property-read CurriculumDocumentStatus $status
 * @property-read int $version
 * @property-read int|null $uploaded_by
 * @property-read CarbonInterface|null $published_at
 * @property-read CarbonInterface|null $archived_at
 * @property-read string|null $gemini_file_name
 * @property-read string|null $gemini_document_name
 * @property-read CurriculumIndexStatus $index_status
 * @property-read string|null $index_error
 * @property-read Course $course
 * @property-read CourseLevel $level
 * @property-read CourseUnit $unit
 * @property-read CourseLesson|null $lesson
 * @property-read User|null $uploader
 */
final class CurriculumDocument extends Model
{
    /** @use HasFactory<CurriculumDocumentFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'status' => CurriculumDocumentStatus::class,
            'index_status' => CurriculumIndexStatus::class,
            'version' => 'integer',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
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
     * @return BelongsTo<CourseLesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class, 'course_lesson_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isTutorRetrievable(): bool
    {
        return $this->status === CurriculumDocumentStatus::Published;
    }

    /**
     * @param  Builder<CurriculumDocument>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', CurriculumDocumentStatus::Published);
    }

    /**
     * @param  Builder<CurriculumDocument>  $query
     */
    #[Scope]
    protected function withinAssignment(Builder $query, StudentAssignment $assignment): void
    {
        $query->where('course_id', $assignment->course_id)
            ->where('course_level_id', $assignment->course_level_id)
            ->where('course_unit_id', $assignment->course_unit_id);
    }
}
