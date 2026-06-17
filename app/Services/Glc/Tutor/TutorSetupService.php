<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\UserRole;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\StudentAssignment;
use App\Models\User;
use Illuminate\Http\Request;

final class TutorSetupService
{
    /**
     * @return array<string, mixed>
     */
    public function payload(User $actor, Request $request): array
    {
        $canViewAll = $actor->role instanceof UserRole && $actor->role->canViewAllStudents();
        $canManageCurriculum = $actor->role instanceof UserRole && $actor->role->canManageCurriculum();

        $scopeSelection = $this->resolveScopeSelection($request);
        $scope = $this->resolveScope($scopeSelection);
        $studentIds = $this->resolveStudentIds($request);

        $students = ($canViewAll
            ? User::query()->where('role', UserRole::Student)
            : $actor->assignedStudents())
            ->with(['studentAssignment.course', 'studentAssignment.level', 'studentAssignment.unit'])
            ->orderBy('name')
            ->get();

        $selectedStudents = $students->whereIn('id', $studentIds)->values();

        $materials = $scope !== null
            ? $this->materialsForScope($scope)
            : $this->emptyMaterials();

        $assignmentMatchesScope = $this->allSelectedStudentsAssigned($selectedStudents, $scope);
        $materialsReady = $materials['ready'];
        $setupComplete = $materialsReady && $assignmentMatchesScope && $selectedStudents->isNotEmpty();

        return [
            'canManageCurriculum' => $canManageCurriculum,
            'canViewAll' => $canViewAll,
            'courses' => $this->coursesTree(),
            'students' => $students
                ->map(fn (User $student): array => $this->studentRow($student, $actor))
                ->all(),
            'scope' => $scope,
            'scopeSelection' => $scopeSelection,
            'selectedStudentIds' => $selectedStudents->pluck('id')->all(),
            'materials' => $materials,
            'assignmentMatchesScope' => $assignmentMatchesScope,
            'setupComplete' => $setupComplete,
            'status' => $request->session()->get('status'),
        ];
    }

    /**
     * @return list<int>
     */
    private function resolveStudentIds(Request $request): array
    {
        $studentIds = collect($request->input('student_ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($studentIds !== []) {
            return $studentIds;
        }

        $legacyStudentId = $request->integer('student_id') ?: null;

        return $legacyStudentId !== null ? [$legacyStudentId] : [];
    }

    /**
     * @return array{course_id: int|null, course_level_id: int|null, course_unit_id: int|null}
     */
    private function resolveScopeSelection(Request $request): array
    {
        $courseId = $request->integer('course_id') ?: null;
        $levelId = $request->integer('course_level_id') ?: null;
        $unitId = $request->integer('course_unit_id') ?: null;

        if ($courseId !== null && ! Course::query()->whereKey($courseId)->exists()) {
            $courseId = null;
            $levelId = null;
            $unitId = null;
        }

        if ($levelId !== null) {
            $level = CourseLevel::query()
                ->whereKey($levelId)
                ->when($courseId !== null, fn ($query) => $query->where('course_id', $courseId))
                ->first();

            if ($level === null) {
                $levelId = null;
                $unitId = null;
            } elseif ($courseId === null) {
                $courseId = $level->course_id;
            }
        }

        if ($unitId !== null) {
            $unit = CourseUnit::query()
                ->whereKey($unitId)
                ->when($levelId !== null, fn ($query) => $query->where('course_level_id', $levelId))
                ->first();

            if ($unit === null) {
                $unitId = null;
            } elseif ($levelId === null) {
                $levelId = $unit->course_level_id;
                $courseId = CourseLevel::query()->whereKey($levelId)->value('course_id');
            }
        }

        return [
            'course_id' => $courseId,
            'course_level_id' => $levelId,
            'course_unit_id' => $unitId,
        ];
    }

    /**
     * @param  array{course_id: int|null, course_level_id: int|null, course_unit_id: int|null}  $selection
     * @return array<string, mixed>|null
     */
    private function resolveScope(array $selection): ?array
    {
        $courseId = $selection['course_id'];
        $levelId = $selection['course_level_id'];
        $unitId = $selection['course_unit_id'];

        if ($courseId === null || $levelId === null || $unitId === null) {
            return null;
        }

        $unit = CourseUnit::query()
            ->with(['level.course'])
            ->whereKey($unitId)
            ->where('course_level_id', $levelId)
            ->first();

        if (! $unit instanceof CourseUnit
            || $unit->level->course_id !== $courseId) {
            return null;
        }

        return [
            'course_id' => $courseId,
            'course_level_id' => $levelId,
            'course_unit_id' => $unitId,
            'course' => $unit->level->course->name,
            'level' => $unit->level->name,
            'unit' => $unit->name,
        ];
    }

    /**
     * @param  array<string, mixed>  $scope
     * @return array<string, mixed>
     */
    private function materialsForScope(array $scope): array
    {
        $documents = CurriculumDocument::query()
            ->with('lesson')
            ->where('course_id', $scope['course_id'])
            ->where('course_level_id', $scope['course_level_id'])
            ->where('course_unit_id', $scope['course_unit_id'])
            ->whereNot('status', CurriculumDocumentStatus::Archived)
            ->orderBy('title')
            ->get();

        $published = $documents->filter(
            fn (CurriculumDocument $document): bool => $document->isTutorRetrievable(),
        );

        return [
            'published_count' => $published->count(),
            'draft_count' => $documents->where('status', CurriculumDocumentStatus::Draft)->count(),
            'pending_count' => $documents->whereIn('status', [
                CurriculumDocumentStatus::Publishing,
                CurriculumDocumentStatus::PublishFailed,
            ])->count(),
            'ready' => $published->isNotEmpty(),
            'documents' => $documents->map(fn (CurriculumDocument $document): array => [
                'id' => $document->id,
                'title' => $document->title,
                'lesson' => $document->lesson?->name,
                'state' => $this->documentState($document),
                'state_label' => $this->documentStateLabel($document),
                'show_url' => route('curriculum.documents.show', $document),
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyMaterials(): array
    {
        return [
            'published_count' => 0,
            'draft_count' => 0,
            'pending_count' => 0,
            'ready' => false,
            'documents' => [],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $students
     */
    private function allSelectedStudentsAssigned($students, ?array $scope): bool
    {
        if ($scope === null || $students->isEmpty()) {
            return false;
        }

        return $students->every(
            fn (User $student): bool => $this->assignmentMatchesScope($student, $scope),
        );
    }

    private function assignmentMatchesScope(?User $student, ?array $scope): bool
    {
        if (! $student instanceof User || $scope === null) {
            return false;
        }

        $assignment = $student->studentAssignment;

        if (! $assignment instanceof StudentAssignment) {
            return false;
        }

        return $assignment->course_id === $scope['course_id']
            && $assignment->course_level_id === $scope['course_level_id']
            && $assignment->course_unit_id === $scope['course_unit_id'];
    }

    /**
     * @return array<string, mixed>
     */
    private function studentRow(User $student, User $actor): array
    {
        $assignment = $student->studentAssignment;
        $linked = $actor->role === UserRole::Teacher
            ? $actor->assignedStudents()->whereKey($student->id)->exists()
            : true;

        return [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $student->email,
            'linked' => $linked,
            'consent' => [
                'required' => $student->requiresGuardianConsent(),
                'confirmed' => $student->hasGuardianConsent(),
            ],
            'assignment' => $assignment instanceof StudentAssignment ? [
                'course_id' => $assignment->course_id,
                'course_level_id' => $assignment->course_level_id,
                'course_unit_id' => $assignment->course_unit_id,
                'course' => $assignment->course->name,
                'level' => $assignment->level->name,
                'unit' => $assignment->unit->name,
            ] : null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function coursesTree(): array
    {
        return Course::query()
            ->with(['levels.units'])
            ->orderBy('name')
            ->get()
            ->map(fn (Course $course): array => [
                'id' => $course->id,
                'name' => $course->name,
                'levels' => $course->levels->map(fn (CourseLevel $level): array => [
                    'id' => $level->id,
                    'name' => $level->name,
                    'units' => $level->units->map(fn (CourseUnit $unit): array => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                    ])->all(),
                ])->all(),
            ])
            ->all();
    }

    private function documentState(CurriculumDocument $document): string
    {
        return match ($document->status) {
            CurriculumDocumentStatus::Archived => 'archived',
            CurriculumDocumentStatus::Draft => 'draft',
            CurriculumDocumentStatus::Publishing => 'publishing',
            CurriculumDocumentStatus::Published => $document->isTutorRetrievable() ? 'published' : 'publishing',
            CurriculumDocumentStatus::PublishFailed => 'publish_failed',
        };
    }

    private function documentStateLabel(CurriculumDocument $document): string
    {
        return match ($this->documentState($document)) {
            'draft' => 'Draft — needs preview & publish',
            'publishing' => 'Being prepared for the tutor',
            'published' => 'Live for the AI Tutor',
            'publish_failed' => "Couldn't be published — try again",
            'archived' => 'Archived',
            default => 'Draft',
        };
    }
}
