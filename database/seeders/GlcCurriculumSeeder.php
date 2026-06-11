<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLesson;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class GlcCurriculumSeeder extends Seeder
{
    private const array STRUCTURE = [
        'General English' => ['Starter', 'Elementary', 'Intermediate'],
        'Academic English' => ['Foundation', 'Advanced'],
    ];

    private const int UNITS_PER_LEVEL = 4;

    private const int LESSONS_PER_UNIT = 2;

    public function run(): void
    {
        foreach (self::STRUCTURE as $courseName => $levelNames) {
            $course = Course::query()->firstOrCreate(
                ['name' => $courseName],
                ['description' => $courseName.' programme curriculum (GLC placeholder content).'],
            );

            foreach ($levelNames as $levelPosition => $levelName) {
                $level = CourseLevel::query()->firstOrCreate(
                    ['course_id' => $course->id, 'name' => $levelName],
                    ['position' => $levelPosition + 1],
                );

                for ($unitNumber = 1; $unitNumber <= self::UNITS_PER_LEVEL; $unitNumber++) {
                    $unit = CourseUnit::query()->firstOrCreate(
                        ['course_level_id' => $level->id, 'name' => 'Unit '.$unitNumber],
                        ['position' => $unitNumber],
                    );

                    $lessons = [];

                    for ($lessonNumber = 1; $lessonNumber <= self::LESSONS_PER_UNIT; $lessonNumber++) {
                        $lessons[$lessonNumber] = CourseLesson::query()->firstOrCreate(
                            ['course_unit_id' => $unit->id, 'name' => 'Lesson '.$lessonNumber],
                            ['position' => $lessonNumber],
                        );
                    }

                    $this->seedDocument($course, $level, $unit, $lessons[1], 'Lesson Summary', CurriculumDocumentStatus::Published);

                    if ($unitNumber <= 2) {
                        $this->seedDocument($course, $level, $unit, null, 'Practice Worksheet', CurriculumDocumentStatus::Draft);
                    }
                }
            }
        }
    }

    private function seedDocument(
        Course $course,
        CourseLevel $level,
        CourseUnit $unit,
        ?CourseLesson $lesson,
        string $type,
        CurriculumDocumentStatus $status,
    ): void {
        $title = sprintf('%s %s %s — %s', $course->name, $level->name, $unit->name, $type);
        $filename = Str::slug($title).'.txt';
        $path = sprintf('glc/curriculum/%d/%s', $course->id, $filename);

        if (! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, $this->documentText($course->name, $level->name, $unit->name, $type));
        }

        CurriculumDocument::query()->firstOrCreate(
            ['course_unit_id' => $unit->id, 'title' => $title],
            [
                'course_id' => $course->id,
                'course_level_id' => $level->id,
                'course_lesson_id' => $lesson?->id,
                'original_filename' => $filename,
                'file_path' => $path,
                'format' => 'txt',
                'extracted_text' => $this->documentText($course->name, $level->name, $unit->name, $type),
                'status' => $status,
                'version' => 1,
                'uploaded_by' => null,
                'published_at' => $status === CurriculumDocumentStatus::Published ? now() : null,
                'index_status' => CurriculumIndexStatus::Pending,
            ],
        );
    }

    private function documentText(string $course, string $level, string $unit, string $type): string
    {
        $header = sprintf("%s — %s — %s\n%s (GLC-created placeholder)\n\n", $course, $level, $unit, $type);

        if ($type === 'Lesson Summary') {
            return $header.implode("\n", [
                'This summary covers the key language points taught in this unit.',
                '',
                'Target grammar: present simple and present continuous in everyday contexts;',
                'question forms and short answers; common irregular verbs.',
                '',
                'Key vocabulary: daily routines, classroom language, describing people and places,',
                'time expressions, and frequency adverbs.',
                '',
                'Reading focus: identifying the main idea of a short passage and scanning for details.',
                'Writing focus: building complete sentences and linking ideas with and, but, because.',
                '',
                'Study tips: review the vocabulary list aloud, write three example sentences for each',
                'grammar point, and reread the unit passage before attempting the practice tasks.',
            ]);
        }

        return $header.implode("\n", [
            'Practice tasks for self-study. Attempt every task before checking with your teacher.',
            '',
            'Task 1 — Vocabulary: match each word from the unit list to its definition, then use',
            'five of the words in your own sentences.',
            '',
            'Task 2 — Grammar: complete the gap-fill sentences using the correct verb form.',
            'Explain in one sentence why you chose each answer.',
            '',
            'Task 3 — Reading: read the short passage from the unit and answer the five',
            'comprehension questions in full sentences.',
            '',
            'Task 4 — Writing: write a short paragraph (60-80 words) on the unit topic using at',
            'least three new vocabulary items and one linking word.',
        ]);
    }
}
