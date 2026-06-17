<?php

declare(strict_types=1);

namespace App\Models\Glc;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumMaterialKind;
use App\Models\User;
use Carbon\CarbonInterface;
use Database\Factories\Glc\CurriculumDocumentVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $curriculum_document_id
 * @property-read int $version
 * @property-read string $title
 * @property-read CurriculumMaterialKind $material_kind
 * @property-read string $original_filename
 * @property-read string $file_path
 * @property-read string $format
 * @property-read string|null $extracted_text
 * @property-read CurriculumDocumentStatus $status
 * @property-read CarbonInterface|null $published_at
 * @property-read CarbonInterface|null $archived_at
 * @property-read string|null $gemini_file_name
 * @property-read string|null $gemini_document_name
 * @property-read int|null $uploaded_by
 * @property-read CurriculumDocument $document
 * @property-read User|null $uploader
 */
final class CurriculumDocumentVersion extends Model
{
    /** @use HasFactory<CurriculumDocumentVersionFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'material_kind' => CurriculumMaterialKind::class,
            'status' => CurriculumDocumentStatus::class,
            'version' => 'integer',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CurriculumDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(CurriculumDocument::class, 'curriculum_document_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
