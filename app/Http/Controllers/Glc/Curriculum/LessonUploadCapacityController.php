<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Http\Controllers\Glc\Curriculum\Concerns\AuthorizesCurriculum;
use App\Models\Glc\CourseLesson;
use App\Services\Glc\Curriculum\CurriculumPermission;
use App\Services\Glc\Curriculum\CurriculumUploadLimits;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class LessonUploadCapacityController
{
    use AuthorizesCurriculum;

    public function __construct(private CurriculumUploadLimits $limits) {}

    public function __invoke(Request $request, CourseLesson $lesson): JsonResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::View);

        return response()->json($this->limits->capacityForLesson($lesson->id));
    }
}
