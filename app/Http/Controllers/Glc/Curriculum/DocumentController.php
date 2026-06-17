<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\CurriculumIndexStatus;
use App\Enums\Glc\CurriculumMaterialKind;
use App\Http\Concerns\Glc\ValidatesHierarchy;
use App\Http\Controllers\Glc\Curriculum\Concerns\AuthorizesCurriculum;
use App\Models\Glc\Course;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\CurriculumDocumentVersion;
use App\Models\User;
use App\Services\Glc\AuditLogger;
use App\Services\Glc\Curriculum\CurriculumIndexService;
use App\Services\Glc\Curriculum\CurriculumPermission;
use App\Services\Glc\Curriculum\CurriculumPermissions;
use App\Services\Glc\Curriculum\CurriculumUploadLimits;
use App\Services\Glc\Curriculum\CurriculumUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

final readonly class DocumentController
{
    use AuthorizesCurriculum;
    use ValidatesHierarchy;

    private const array STATES = ['draft', 'publishing', 'published', 'publish_failed', 'archived'];

    public function __construct(
        private CurriculumUploadService $uploads,
        private CurriculumIndexService $indexService,
        private CurriculumUploadLimits $uploadLimits,
        private CurriculumPermissions $permissions,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizeCurriculum($request, CurriculumPermission::View);

        $filters = $request->validate([
            'course_id' => ['nullable', 'integer'],
            'course_level_id' => ['nullable', 'integer'],
            'course_unit_id' => ['nullable', 'integer'],
            'course_lesson_id' => ['nullable', 'integer'],
            'material_kind' => ['nullable', Rule::enum(CurriculumMaterialKind::class)],
            'state' => ['nullable', Rule::in(self::STATES)],
            'status' => ['nullable', Rule::enum(CurriculumDocumentStatus::class)],
            'index_status' => ['nullable', Rule::enum(CurriculumIndexStatus::class)],
        ]);

        $documents = CurriculumDocument::query()
            ->with(['course', 'level', 'unit', 'lesson'])
            ->when($filters['course_id'] ?? null, fn ($query, $value) => $query->where('course_id', $value))
            ->when($filters['course_level_id'] ?? null, fn ($query, $value) => $query->where('course_level_id', $value))
            ->when($filters['course_unit_id'] ?? null, fn ($query, $value) => $query->where('course_unit_id', $value))
            ->when($filters['course_lesson_id'] ?? null, fn ($query, $value) => $query->where('course_lesson_id', $value))
            ->when($filters['material_kind'] ?? null, fn ($query, $value) => $query->where('material_kind', $value))
            ->when($filters['state'] ?? null, fn (Builder $query, string $state) => $this->applyStateFilter($query, $state))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['index_status'] ?? null, fn ($query, $value) => $query->where('index_status', $value))
            ->latest('updated_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (CurriculumDocument $document): array => [
                'id' => $document->id,
                'title' => $document->title,
                'material_kind' => $document->material_kind->value,
                'material_kind_label' => $document->material_kind->label(),
                'course' => $document->course->name,
                'level' => $document->level->name,
                'unit' => $document->unit->name,
                'lesson' => $document->lesson?->name,
                'format' => $document->format,
                'status' => $document->status->value,
                'status_label' => $document->status->label(),
                'index_status' => $document->index_status->value,
                'index_status_label' => $document->index_status->label(),
                'state' => $this->documentState($document),
                'state_label' => $this->documentStateLabel($document),
                'version' => $document->version,
                'updated_at' => $document->updated_at?->format('d M Y H:i'),
            ]);

        return Inertia::render('glc/curriculum/index', [
            'documents' => $documents,
            'filters' => $filters,
            'tree' => $this->tree(),
            'materialKinds' => $this->materialKindOptions(),
            'upload' => [
                'allowedExtensions' => config('glc.curriculum.allowed_extensions'),
                'maxFileSizeKb' => config()->integer('glc.curriculum.max_file_size_kb'),
                'maxBulkFiles' => config()->integer('glc.curriculum.max_bulk_files'),
                'maxDocumentsPerLesson' => config()->integer('glc.curriculum.max_documents_per_lesson'),
            ],
            'bulkReport' => $request->session()->get('bulk_report'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function show(Request $request, CurriculumDocument $document): Response
    {
        $this->authorizeCurriculum($request, CurriculumPermission::View);

        $document->load(['course', 'level', 'unit', 'lesson', 'uploader']);

        /** @var User $user */
        $user = $request->user();

        $canRestore = $this->permissions->can($user, CurriculumPermission::RestoreVersion);

        $versions = CurriculumDocumentVersion::query()
            ->where('curriculum_document_id', $document->id)
            ->orderByDesc('version')
            ->get()
            ->map(fn (CurriculumDocumentVersion $version): array => [
                'version' => $version->version,
                'original_filename' => $version->original_filename,
                'published_at' => $version->published_at?->format('d M Y H:i'),
                'created_at' => $version->created_at?->format('d M Y H:i'),
                'can_restore' => $canRestore && Storage::disk('local')->exists($version->file_path),
            ])
            ->all();

        return Inertia::render('glc/curriculum/show', [
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'material_kind' => $document->material_kind->value,
                'material_kind_label' => $document->material_kind->label(),
                'course' => $document->course->name,
                'level' => $document->level->name,
                'unit' => $document->unit->name,
                'lesson' => $document->lesson?->name,
                'format' => $document->format,
                'original_filename' => $document->original_filename,
                'status' => $document->status->value,
                'status_label' => $document->status->label(),
                'index_status' => $document->index_status->value,
                'index_status_label' => $document->index_status->label(),
                'index_error' => $document->index_error,
                'state' => $this->documentState($document),
                'state_label' => $this->documentStateLabel($document),
                'version' => $document->version,
                'has_stored_file' => Storage::disk('local')->exists($document->file_path),
                'file_size_label' => $this->fileSizeLabel($document->file_path),
                'uploaded_by' => $document->uploader?->name,
                'published_at' => $document->published_at?->format('d M Y H:i'),
                'archived_at' => $document->archived_at?->format('d M Y H:i'),
                'created_at' => $document->created_at?->format('d M Y H:i'),
                'updated_at' => $document->updated_at?->format('d M Y H:i'),
            ],
            'versions' => $versions,
            'canDelete' => $this->permissions->can($user, CurriculumPermission::Delete),
            'canPublish' => $this->permissions->can($user, CurriculumPermission::Publish),
            'canReplace' => $this->permissions->can($user, CurriculumPermission::Replace),
            'canArchive' => $this->permissions->can($user, CurriculumPermission::Archive),
            'canReindex' => $this->permissions->can($user, CurriculumPermission::Reindex),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::Upload);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'material_kind' => $this->materialKindRules(),
            'file' => ['required', ...$this->fileRules()],
            ...$this->hierarchyRules($request),
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];

        if (isset($validated['course_lesson_id'])) {
            $this->uploadLimits->assertLessonCapacity((int) $validated['course_lesson_id'], 1);
        }

        /** @var User $user */
        $user = $request->user();

        try {
            $document = $this->uploads->store($file, [
                'title' => $validated['title'],
                'material_kind' => CurriculumMaterialKind::from($validated['material_kind']),
                'course_id' => (int) $validated['course_id'],
                'course_level_id' => (int) $validated['course_level_id'],
                'course_unit_id' => (int) $validated['course_unit_id'],
                'course_lesson_id' => isset($validated['course_lesson_id']) ? (int) $validated['course_lesson_id'] : null,
            ], $user);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }

        return redirect()
            ->route('curriculum.documents.show', $document)
            ->with('status', 'Document uploaded as a draft. Review the file details below, then publish it to the AI Tutor.');
    }

    public function destroy(Request $request, CurriculumDocument $document): RedirectResponse
    {
        $this->authorizeCurriculum($request, CurriculumPermission::Delete);

        $this->indexService->deleteStoreDocumentQuietly($document->gemini_document_name);

        Storage::disk('local')->delete($document->file_path);

        $this->auditLogger->log(AuditAction::CurriculumDeleted, $request->user(), $document, [
            'title' => $document->title,
            'file_path' => $document->file_path,
            'version' => $document->version,
        ]);

        $document->delete();

        return redirect()
            ->route('curriculum.index')
            ->with('status', 'Document permanently deleted.');
    }

    /**
     * @param  Builder<CurriculumDocument>  $query
     * @return Builder<CurriculumDocument>
     */
    private function applyStateFilter(Builder $query, string $state): Builder
    {
        return match ($state) {
            'draft' => $query->where('status', CurriculumDocumentStatus::Draft),
            'publishing' => $query->where('status', CurriculumDocumentStatus::Publishing),
            'published' => $query->where('status', CurriculumDocumentStatus::Published),
            'publish_failed' => $query->where('status', CurriculumDocumentStatus::PublishFailed),
            'archived' => $query->where('status', CurriculumDocumentStatus::Archived),
            default => $query,
        };
    }

    private function documentState(CurriculumDocument $document): string
    {
        return match ($document->status) {
            CurriculumDocumentStatus::Archived => 'archived',
            CurriculumDocumentStatus::Draft => 'draft',
            CurriculumDocumentStatus::Publishing => 'publishing',
            CurriculumDocumentStatus::Published => 'published',
            CurriculumDocumentStatus::PublishFailed => 'publish_failed',
        };
    }

    private function documentStateLabel(CurriculumDocument $document): string
    {
        return match ($this->documentState($document)) {
            'draft' => 'Draft — not visible to students',
            'publishing' => 'Being prepared...',
            'published' => 'Live for students',
            'publish_failed' => 'Couldn\'t be published — try again',
            'archived' => 'Archived',
            default => 'Draft — not visible to students',
        };
    }

    private function fileSizeLabel(string $filePath): ?string
    {
        if (! Storage::disk('local')->exists($filePath)) {
            return null;
        }

        $bytes = Storage::disk('local')->size($filePath);

        if ($bytes === false) {
            return null;
        }

        if ($bytes >= 1_048_576) {
            return sprintf('%.1f MB', $bytes / 1_048_576);
        }

        if ($bytes >= 1024) {
            return sprintf('%d KB', (int) round($bytes / 1024));
        }

        return sprintf('%d B', $bytes);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tree(): array
    {
        return Course::query()
            ->with(['levels.units.lessons'])
            ->orderBy('name')
            ->get()
            ->map(fn (Course $course): array => [
                'id' => $course->id,
                'name' => $course->name,
                'levels' => $course->levels->map(fn ($level): array => [
                    'id' => $level->id,
                    'name' => $level->name,
                    'position' => $level->position,
                    'units' => $level->units->map(fn ($unit): array => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'position' => $unit->position,
                        'lessons' => $unit->lessons->map(fn ($lesson): array => [
                            'id' => $lesson->id,
                            'name' => $lesson->name,
                            'position' => $lesson->position,
                        ])->all(),
                    ])->all(),
                ])->all(),
            ])
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function materialKindOptions(): array
    {
        return array_map(
            fn (CurriculumMaterialKind $kind): array => [
                'value' => $kind->value,
                'label' => $kind->label(),
            ],
            CurriculumMaterialKind::cases(),
        );
    }
}
