<?php

declare(strict_types=1);

namespace App\Http\Concerns\Glc;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait ValidatesHierarchy
{
    /**
     * @return array<string, array<int, mixed>>
     */
    private function hierarchyRules(Request $request): array
    {
        return [
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'course_level_id' => [
                'required',
                'integer',
                Rule::exists('course_levels', 'id')->where('course_id', $request->integer('course_id')),
            ],
            'course_unit_id' => [
                'required',
                'integer',
                Rule::exists('course_units', 'id')->where('course_level_id', $request->integer('course_level_id')),
            ],
            'course_lesson_id' => [
                'nullable',
                'integer',
                Rule::exists('course_lessons', 'id')->where('course_unit_id', $request->integer('course_unit_id')),
            ],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function fileRules(): array
    {
        /** @var list<string> $extensions */
        $extensions = config('glc.curriculum.allowed_extensions', []);

        return [
            'file',
            'extensions:'.implode(',', $extensions),
            'max:'.config()->integer('glc.curriculum.max_file_size_kb'),
        ];
    }
}
