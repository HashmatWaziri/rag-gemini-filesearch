<?php

declare(strict_types=1);

use App\Enums\Glc\CurriculumMaterialKind;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLesson;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Models\User;
use App\Services\Glc\Curriculum\CurriculumUploadLimits;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');

    $this->supervisor = User::factory()->academicSupervisor()->create();
    $this->course = Course::factory()->create();
    $this->level = CourseLevel::factory()->for($this->course)->create();
    $this->unit = CourseUnit::factory()->create(['course_level_id' => $this->level->id]);
    $this->lesson = CourseLesson::factory()->create(['course_unit_id' => $this->unit->id]);

    $this->tags = [
        'course_id' => $this->course->id,
        'course_level_id' => $this->level->id,
        'course_unit_id' => $this->unit->id,
        'course_lesson_id' => $this->lesson->id,
    ];
});

it('returns upload capacity for a lesson', function (): void {
    CurriculumDocument::factory()->count(2)->create([
        'course_id' => $this->course->id,
        'course_level_id' => $this->level->id,
        'course_unit_id' => $this->unit->id,
        'course_lesson_id' => $this->lesson->id,
    ]);

    config([
        'glc.curriculum.max_documents_per_lesson' => 30,
        'glc.curriculum.max_bulk_files' => 20,
    ]);

    $this->actingAs($this->supervisor)
        ->getJson(route('curriculum.lessons.upload-capacity', $this->lesson))
        ->assertOk()
        ->assertJson([
            'existing_count' => 2,
            'max_per_lesson' => 30,
            'max_per_request' => 20,
            'remaining_slots' => 28,
            'max_rows' => 20,
        ]);
});

it('rejects lesson uploads that exceed the per-lesson document limit', function (): void {
    config(['glc.curriculum.max_documents_per_lesson' => 2]);

    CurriculumDocument::factory()->count(2)->create([
        'course_id' => $this->course->id,
        'course_level_id' => $this->level->id,
        'course_unit_id' => $this->unit->id,
        'course_lesson_id' => $this->lesson->id,
    ]);

    $this->actingAs($this->supervisor)->post(route('curriculum.documents.lesson-materials'), [
        ...$this->tags,
        'material_kinds' => [CurriculumMaterialKind::Summary->value],
        'files' => [UploadedFile::fake()->createWithContent('extra.txt', 'Extra')],
    ])->assertSessionHasErrors('files');

    expect(CurriculumDocument::query()->count())->toBe(2);
});

it('rejects duplicate filenames within one lesson upload batch', function (): void {
    $this->actingAs($this->supervisor)->post(route('curriculum.documents.lesson-materials'), [
        ...$this->tags,
        'material_kinds' => [
            CurriculumMaterialKind::Summary->value,
            CurriculumMaterialKind::Notes->value,
        ],
        'files' => [
            UploadedFile::fake()->createWithContent('notes.txt', 'One'),
            UploadedFile::fake()->createWithContent('notes.txt', 'Two'),
        ],
    ])->assertSessionHasErrors('files.1');

    expect(CurriculumDocument::query()->count())->toBe(0);
});

it('rejects more files than the configured per-request maximum', function (): void {
    config(['glc.curriculum.max_bulk_files' => 2]);

    $this->actingAs($this->supervisor)->post(route('curriculum.documents.lesson-materials'), [
        ...$this->tags,
        'material_kinds' => [
            CurriculumMaterialKind::Summary->value,
            CurriculumMaterialKind::Notes->value,
            CurriculumMaterialKind::Worksheet->value,
        ],
        'files' => [
            UploadedFile::fake()->createWithContent('one.txt', 'one'),
            UploadedFile::fake()->createWithContent('two.txt', 'two'),
            UploadedFile::fake()->createWithContent('three.txt', 'three'),
        ],
    ])->assertSessionHasErrors('files');

    expect(CurriculumDocument::query()->count())->toBe(0);
});

it('throttles curriculum upload routes per staff user', function (): void {
    config(['glc.curriculum.uploads_per_minute' => 2]);

    RateLimiter::clear('curriculum-upload:'.$this->supervisor->id);

    $payload = [
        ...$this->tags,
        'material_kinds' => [CurriculumMaterialKind::Summary->value],
        'files' => [UploadedFile::fake()->createWithContent('summary.txt', 'Summary')],
    ];

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.lesson-materials'), $payload)
        ->assertRedirect();

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.lesson-materials'), $payload)
        ->assertRedirect();

    $this->actingAs($this->supervisor)
        ->post(route('curriculum.documents.lesson-materials'), $payload)
        ->assertStatus(429);
});

it('computes remaining lesson slots through the upload limits service', function (): void {
    config(['glc.curriculum.max_documents_per_lesson' => 5]);

    CurriculumDocument::factory()->count(3)->create([
        'course_lesson_id' => $this->lesson->id,
    ]);

    $limits = app(CurriculumUploadLimits::class);

    expect($limits->remainingLessonSlots($this->lesson->id))->toBe(2)
        ->and($limits->capacityForLesson($this->lesson->id)['max_rows'])->toBe(2);
});
