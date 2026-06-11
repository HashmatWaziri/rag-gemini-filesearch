<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumDocument>
 */
final class CurriculumDocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'course_level_id' => CourseLevel::factory(),
            'course_unit_id' => CourseUnit::factory(),
            'course_lesson_id' => null,
            'title' => fake()->sentence(3),
            'original_filename' => 'worksheet.pdf',
            'file_path' => 'glc/curriculum/worksheet.pdf',
            'format' => 'pdf',
            'extracted_text' => fake()->paragraphs(2, true),
            'status' => CurriculumDocumentStatus::Draft,
            'version' => 1,
            'uploaded_by' => null,
            'index_status' => CurriculumIndexStatus::Pending,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (CurriculumDocument $document): void {
            $level = CourseLevel::query()->find($document->course_level_id);

            if ($level !== null && $document->course_id !== $level->course_id) {
                $document->course_id = $level->course_id;
            }

            $unit = CourseUnit::query()->find($document->course_unit_id);

            if ($unit !== null && $unit->course_level_id !== $document->course_level_id) {
                $unit->update(['course_level_id' => $document->course_level_id]);
            }
        });
    }

    public function published(): self
    {
        return $this->state(fn (): array => [
            'status' => CurriculumDocumentStatus::Published,
            'published_at' => now(),
            'index_status' => CurriculumIndexStatus::Indexed,
            'gemini_file_name' => 'files/'.fake()->uuid(),
            'gemini_document_name' => 'fileSearchStores/store/documents/'.fake()->uuid(),
        ]);
    }

    public function archived(): self
    {
        return $this->state(fn (): array => [
            'status' => CurriculumDocumentStatus::Archived,
            'archived_at' => now(),
            'index_status' => CurriculumIndexStatus::Removed,
        ]);
    }
}
