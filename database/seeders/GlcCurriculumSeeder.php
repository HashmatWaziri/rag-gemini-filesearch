<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Glc\Course;
use App\Models\Glc\CourseLesson;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Services\Glc\Curriculum\CurriculumIndexService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

final class GlcCurriculumSeeder extends Seeder
{
    private const string COURSE_NAME = 'Beehive';

    /** @var list<string> */
    private const array STARTER_LESSONS = [
        'Lesson 1 — Words',
        'Lesson 2 — Grammar',
        'Lesson 3 — Words and Grammar',
        'Lesson 4 — Numbers',
        'Lesson 5 — Story',
        'Lesson 6 — Phonics',
    ];

    /** @var list<string> */
    private const array STANDARD_LESSONS = [
        'Lesson 1 — Words',
        'Lesson 2 — Grammar',
        'Lesson 3 — Words and Grammar',
        'Lesson 4 — Story',
        'Lesson 5 — Skills and Culture',
        'Lesson 6 — Writing Focus and Project Review',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const array LEVEL_UNITS = [
        'Starter' => [
            'Hello!',
            'Let\'s learn!',
            'Colours',
            'Farm animals',
            'Let\'s eat!',
            'Let\'s play!',
            'Sea animals',
            'My body',
            'Let\'s celebrate!',
        ],
        'Level 1' => [
            'Hello again!',
            'At school',
            'My things',
            'Fun with friends',
            'Outdoors',
            'My body',
            'My family',
            'My clothes',
            'Food',
            'At home',
            'At the farm',
        ],
        'Level 2' => [
            'Hello again!',
            'Time for school',
            'Mealtime',
            'Wild animals',
            'My favourite things',
            'Around town',
            'At the weekend',
            'My day',
            'My talents',
            'My home',
            'Days out',
        ],
        'Level 3' => [
            'A new year',
            'Our friends',
            'In the city',
            'Our busy world',
            'Let\'s explore!',
            'Healthy living',
            'In the kitchen',
            'Family life',
            'Our history',
            'School life',
            'Holiday plans',
        ],
        'Level 4' => [
            'Let\'s cook!',
            'The world of animals',
            'Fun at home',
            'My week at school',
            'Attractions',
            'Our community',
            'Future travel',
            'Making music',
            'The world of games',
            'Aches and pains',
            'Exciting adventures',
        ],
        'Level 5' => [
            'Big numbers!',
            'Travel in the city',
            'The seasons',
            'The environment',
            'A trip to the theatre',
            'World food',
            'Let\'s connect',
            'In the countryside',
            'A journey to space',
            'Life in the past',
            'Helping our community',
        ],
        'Level 6' => [
            'Let\'s tidy up!',
            'The world of work',
            'Health and medicine',
            'Let\'s go!',
            'At the art gallery',
            'Let\'s play music!',
            'Science and inventions',
            'Let\'s go shopping!',
            'Our planet',
            'At the wildlife park',
            'Celebrations',
        ],
    ];

    /** @var list<string> */
    private const array LEGACY_COURSE_NAMES = [
        'General English',
        'Academic English',
    ];

    public function run(): void
    {
        $this->removeLegacyCurriculum();

        $course = Course::query()->firstOrCreate(
            ['name' => self::COURSE_NAME],
            ['description' => 'Oxford Beehive — official course hierarchy for GLC tutor content tagging.'],
        );

        foreach (array_keys(self::LEVEL_UNITS) as $position => $levelName) {
            $level = CourseLevel::query()->firstOrCreate(
                ['course_id' => $course->id, 'name' => $levelName],
                ['position' => $position + 1],
            );

            $lessonsForLevel = $levelName === 'Starter'
                ? self::STARTER_LESSONS
                : self::STANDARD_LESSONS;

            foreach (self::LEVEL_UNITS[$levelName] as $unitPosition => $unitName) {
                $unit = CourseUnit::query()->firstOrCreate(
                    ['course_level_id' => $level->id, 'name' => $unitName],
                    ['position' => $unitPosition + 1],
                );

                foreach ($lessonsForLevel as $lessonPosition => $lessonName) {
                    CourseLesson::query()->firstOrCreate(
                        ['course_unit_id' => $unit->id, 'name' => $lessonName],
                        ['position' => $lessonPosition + 1],
                    );
                }
            }
        }
    }

    private function removeLegacyCurriculum(): void
    {
        $indexService = app(CurriculumIndexService::class);

        $legacyCourses = Course::query()
            ->whereIn('name', self::LEGACY_COURSE_NAMES)
            ->get();

        foreach ($legacyCourses as $course) {
            CurriculumDocument::query()
                ->where('course_id', $course->id)
                ->each(function (CurriculumDocument $document) use ($indexService): void {
                    $indexService->deleteStoreDocumentQuietly($document->gemini_document_name);
                    Storage::disk('local')->delete($document->file_path);
                });

            $course->delete();
        }
    }
}
