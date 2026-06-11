<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Models\Glc\CourseLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final readonly class CourseLevelController
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        CourseLevel::query()->create([
            ...$validated,
            'position' => $validated['position']
                ?? (int) CourseLevel::query()->where('course_id', $validated['course_id'])->max('position') + 1,
        ]);

        return back()->with('status', 'Level created.');
    }

    public function update(Request $request, CourseLevel $level): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $level->update(array_filter($validated, fn ($value): bool => $value !== null));

        return back()->with('status', 'Level updated.');
    }

    public function destroy(CourseLevel $level): RedirectResponse
    {
        $level->delete();

        return back()->with('status', 'Level deleted, including its units, lessons, and documents.');
    }
}
