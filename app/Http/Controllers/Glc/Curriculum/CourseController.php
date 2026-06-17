<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Http\Controllers\Glc\Curriculum\Concerns\AuthorizesCurriculum;
use App\Models\Glc\Course;
use App\Services\Glc\Curriculum\CurriculumPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class CourseController
{
    use AuthorizesCurriculum;

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::Upload);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Course::query()->create($validated);

        return back()->with('status', 'Course created.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::Upload);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $course->update($validated);

        return back()->with('status', 'Course updated.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::Upload);

        $course->delete();

        return back()->with('status', 'Course deleted, including its levels, units, lessons, and documents.');
    }
}
