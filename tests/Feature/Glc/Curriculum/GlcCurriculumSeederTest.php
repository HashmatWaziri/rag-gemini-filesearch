<?php

declare(strict_types=1);

use App\Models\Glc\Course;
use App\Models\Glc\CourseLesson;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use Database\Seeders\GlcCurriculumSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
});

it('seeds the Beehive hierarchy without any curriculum documents', function (): void {
    $this->seed(GlcCurriculumSeeder::class);

    expect(Course::query()->count())->toBe(1)
        ->and(Course::query()->value('name'))->toBe('Beehive')
        ->and(CourseLevel::query()->count())->toBe(7)
        ->and(CourseUnit::query()->count())->toBe(75)
        ->and(CourseLesson::query()->count())->toBe(450)
        ->and(CurriculumDocument::query()->count())->toBe(0);
});

it('is idempotent across repeated runs', function (): void {
    $this->seed(GlcCurriculumSeeder::class);
    $this->seed(GlcCurriculumSeeder::class);

    expect(Course::query()->count())->toBe(1)
        ->and(CourseLevel::query()->count())->toBe(7)
        ->and(CourseUnit::query()->count())->toBe(75)
        ->and(CourseLesson::query()->count())->toBe(450);
});

it('seeds starter units and lessons from the Beehive scope', function (): void {
    $this->seed(GlcCurriculumSeeder::class);

    $starter = CourseLevel::query()->where('name', 'Starter')->firstOrFail();

    expect(CourseUnit::query()->where('course_level_id', $starter->id)->count())->toBe(9)
        ->and(CourseUnit::query()->where('course_level_id', $starter->id)->orderBy('position')->value('name'))->toBe('Hello!')
        ->and(CourseLesson::query()->where('course_unit_id', CourseUnit::query()->where('course_level_id', $starter->id)->orderBy('position')->value('id'))->count())->toBe(6)
        ->and(CourseLesson::query()->where('course_unit_id', CourseUnit::query()->where('course_level_id', $starter->id)->orderBy('position')->value('id'))->orderBy('position')->value('name'))->toBe('Lesson 1 — Words');
});

it('removes legacy placeholder courses and their documents when re-seeding', function (): void {
    $legacyCourse = Course::factory()->create(['name' => 'General English']);
    $legacyLevel = CourseLevel::factory()->for($legacyCourse)->create();
    $legacyUnit = CourseUnit::factory()->for($legacyLevel, 'level')->create();
    $legacyDocument = CurriculumDocument::factory()->create([
        'course_id' => $legacyCourse->id,
        'course_level_id' => $legacyLevel->id,
        'course_unit_id' => $legacyUnit->id,
        'file_path' => 'glc/curriculum/legacy/sample.txt',
    ]);

    Storage::disk('local')->put($legacyDocument->file_path, 'legacy placeholder');

    $this->seed(GlcCurriculumSeeder::class);

    expect(Course::query()->where('name', 'General English')->exists())->toBeFalse()
        ->and(CurriculumDocument::query()->whereKey($legacyDocument->id)->exists())->toBeFalse()
        ->and(Storage::disk('local')->exists($legacyDocument->file_path))->toBeFalse()
        ->and(Course::query()->where('name', 'Beehive')->exists())->toBeTrue();
});

it('tags every lesson under a full course-level-unit path', function (): void {
    $this->seed(GlcCurriculumSeeder::class);

    expect(
        CourseLesson::query()
            ->whereHas('unit.level.course', fn ($query) => $query->where('name', 'Beehive'))
            ->count(),
    )->toBe(450);

    expect(
        CourseUnit::query()
            ->whereHas('level', fn ($query) => $query->where('name', 'Level 3'))
            ->orderBy('position')
            ->value('name'),
    )->toBe('A new year');
});
