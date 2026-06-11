<?php

declare(strict_types=1);

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Models\Glc\Course;
use App\Models\Glc\CurriculumDocument;
use Database\Seeders\GlcCurriculumSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
});

it('seeds the launch corpus of 30 placeholder documents', function (): void {
    $this->seed(GlcCurriculumSeeder::class);

    expect(CurriculumDocument::query()->count())->toBe(30)
        ->and(Course::query()->count())->toBe(2)
        ->and(CurriculumDocument::query()->where('status', CurriculumDocumentStatus::Published)->count())->toBe(20)
        ->and(CurriculumDocument::query()->where('status', CurriculumDocumentStatus::Draft)->count())->toBe(10)
        ->and(CurriculumDocument::query()->where('index_status', CurriculumIndexStatus::Pending)->count())->toBe(30)
        ->and(CurriculumDocument::query()->whereNotNull('gemini_document_name')->count())->toBe(0);

    $document = CurriculumDocument::query()->firstOrFail();

    expect($document->format)->toBe('txt')
        ->and($document->extracted_text)->not->toBeEmpty();

    Storage::disk('local')->assertExists($document->file_path);
});

it('is idempotent across repeated runs', function (): void {
    $this->seed(GlcCurriculumSeeder::class);
    $this->seed(GlcCurriculumSeeder::class);

    expect(CurriculumDocument::query()->count())->toBe(30)
        ->and(Course::query()->count())->toBe(2);
});

it('tags every document with a full hierarchy path', function (): void {
    $this->seed(GlcCurriculumSeeder::class);

    expect(
        CurriculumDocument::query()
            ->whereNull('course_id')
            ->orWhereNull('course_level_id')
            ->orWhereNull('course_unit_id')
            ->count(),
    )->toBe(0);

    expect(CurriculumDocument::query()->whereNotNull('course_lesson_id')->count())->toBe(20)
        ->and(CurriculumDocument::query()->whereNull('course_lesson_id')->count())->toBe(10);
});
