<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumMaterialKind;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\CurriculumDocumentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurriculumDocumentVersion>
 */
final class CurriculumDocumentVersionFactory extends Factory
{
    protected $model = CurriculumDocumentVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'curriculum_document_id' => CurriculumDocument::factory(),
            'version' => 1,
            'title' => fake()->sentence(3),
            'material_kind' => fake()->randomElement(CurriculumMaterialKind::cases()),
            'original_filename' => 'worksheet-v1.pdf',
            'file_path' => 'glc/curriculum/1/documents/1/v1.pdf',
            'format' => 'pdf',
            'extracted_text' => null,
            'status' => CurriculumDocumentStatus::Draft,
            'published_at' => null,
            'archived_at' => null,
            'gemini_file_name' => null,
            'gemini_document_name' => null,
            'uploaded_by' => null,
        ];
    }
}
