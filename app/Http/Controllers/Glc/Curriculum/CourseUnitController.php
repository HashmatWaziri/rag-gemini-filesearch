<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Http\Controllers\Glc\Curriculum\Concerns\AuthorizesCurriculum;
use App\Models\Glc\CourseUnit;
use App\Services\Glc\Curriculum\CurriculumPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final readonly class CourseUnitController
{
    use AuthorizesCurriculum;

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::Upload);

        $validated = $request->validate([
            'course_level_id' => ['required', 'integer', Rule::exists('course_levels', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        CourseUnit::query()->create([
            ...$validated,
            'position' => $validated['position']
                ?? (int) CourseUnit::query()->where('course_level_id', $validated['course_level_id'])->max('position') + 1,
        ]);

        return back()->with('status', 'Unit created.');
    }

    public function update(Request $request, CourseUnit $unit): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::Upload);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $unit->update(array_filter($validated, fn ($value): bool => $value !== null));

        return back()->with('status', 'Unit updated.');
    }

    public function destroy(Request $request, CourseUnit $unit): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::Upload);

        $unit->delete();

        return back()->with('status', 'Unit deleted, including its lessons and documents.');
    }
}
