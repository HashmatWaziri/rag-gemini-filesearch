<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Models\Glc\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class CourseController
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Course::query()->create($validated);

        return back()->with('status', 'Course created.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $course->update($validated);

        return back()->with('status', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return back()->with('status', 'Course deleted, including its levels, units, lessons, and documents.');
    }
}
