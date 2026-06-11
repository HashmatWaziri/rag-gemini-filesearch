<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Models\Glc\CourseLesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final readonly class CourseLessonController
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_unit_id' => ['required', 'integer', Rule::exists('course_units', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        CourseLesson::query()->create([
            ...$validated,
            'position' => $validated['position']
                ?? (int) CourseLesson::query()->where('course_unit_id', $validated['course_unit_id'])->max('position') + 1,
        ]);

        return back()->with('status', 'Lesson created.');
    }

    public function update(Request $request, CourseLesson $lesson): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $lesson->update(array_filter($validated, fn ($value): bool => $value !== null));

        return back()->with('status', 'Lesson updated.');
    }

    public function destroy(CourseLesson $lesson): RedirectResponse
    {
        $lesson->delete();

        return back()->with('status', 'Lesson deleted.');
    }
}
