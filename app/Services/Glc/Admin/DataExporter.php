<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\Glc\CurriculumDocumentStatus;
use App\Enums\Glc\UserRole;
use App\Models\Glc\AuditLog;
use App\Models\Glc\Course;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementReview;
use App\Models\Glc\PlacementScore;
use App\Models\Glc\StudentAssignment;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\Glc\TutorProgressReport;
use App\Models\Glc\TutorUsageDaily;
use App\Models\Glc\TutorViolation;
use App\Models\Glc\WritingSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

final class DataExporter
{
    /**
     * @param  list<CurriculumDocumentStatus>|null  $curriculumStatuses  Lifecycle states to include in the curriculum bundle; null includes all.
     */
    public function build(ExportBundle $bundle, ?array $curriculumStatuses = null): string
    {
        $path = sys_get_temp_dir().'/glc-export-'.bin2hex(random_bytes(16)).'.zip';

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not open the export archive for writing.');
        }

        match ($bundle) {
            ExportBundle::Placement => $this->addPlacement($zip),
            ExportBundle::Curriculum => $this->addCurriculum($zip, $curriculumStatuses),
            ExportBundle::Students => $this->addStudents($zip),
            ExportBundle::Tutor => $this->addTutor($zip),
            ExportBundle::Audit => $this->addAudit($zip),
        };

        $zip->close();

        return $path;
    }

    private function addPlacement(ZipArchive $zip): void
    {
        $this->addJsonAndCsv($zip, 'placement/items', PlacementItem::query()->orderBy('id')->get()->toArray());
        $this->addJsonAndCsv($zip, 'placement/attempts', PlacementAttempt::query()->orderBy('id')->get()->toArray());
        $this->addJsonAndCsv($zip, 'placement/answers', PlacementAnswer::query()->orderBy('id')->get()->toArray());
        $this->addJsonAndCsv($zip, 'placement/scores', PlacementScore::query()->orderBy('id')->get()->toArray());
        $this->addJsonAndCsv($zip, 'placement/reviews', PlacementReview::query()->with('notes')->orderBy('id')->get()->toArray());
    }

    /**
     * @param  list<CurriculumDocumentStatus>|null  $statuses
     */
    private function addCurriculum(ZipArchive $zip, ?array $statuses = null): void
    {
        $documents = CurriculumDocument::query()
            ->with(['course:id,name', 'level:id,name', 'unit:id,name', 'lesson:id,name'])
            ->when($statuses !== null, fn (Builder $query) => $query->whereIn('status', $statuses))
            ->orderBy('id')
            ->get();

        $hierarchy = Course::query()
            ->with(['levels.units.lessons'])
            ->orderBy('id')
            ->get()
            ->toArray();

        $manifest = $documents->map(fn (CurriculumDocument $document): array => [
            'id' => $document->id,
            'title' => $document->title,
            'original_filename' => $document->original_filename,
            'storage_path' => $document->file_path,
            'format' => $document->format,
            'course' => $document->course->name,
            'level' => $document->level->name,
            'unit' => $document->unit->name,
            'lesson' => $document->lesson?->name ?? 'Unit-wide',
            'status' => $document->status->value,
            'version' => $document->version,
            'created_at' => $document->created_at->toIso8601String(),
            'updated_at' => $document->updated_at->toIso8601String(),
            'extracted_text_preview' => $document->extracted_text === null
                ? null
                : Str::limit($document->extracted_text, 500),
            'gemini_file_resource_name' => $document->gemini_file_name,
            'gemini_sync_status' => $document->index_status->value,
        ])->all();

        $zip->addFromString('curriculum/documents.json', $this->toJson($documents->toArray()));
        $zip->addFromString('curriculum/hierarchy.json', $this->toJson($hierarchy));
        $zip->addFromString('curriculum/files-manifest.csv', $this->toCsv($manifest));
    }

    private function addStudents(ZipArchive $zip): void
    {
        $students = User::query()
            ->where('role', UserRole::Student)
            ->orderBy('id')
            ->get()
            ->map(fn (User $student): array => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'age' => $student->age,
                'guardian_name' => $student->guardian_name,
                'guardian_email' => $student->guardian_email,
                'requires_guardian_consent' => $student->requiresGuardianConsent(),
                'guardian_consent_confirmed' => $student->hasGuardianConsent(),
                'guardian_consent_confirmed_at' => $student->guardian_consent_confirmed_at?->toIso8601String(),
                'guardian_consent_confirmed_by' => $student->guardian_consent_confirmed_by,
                'created_at' => $student->created_at->toIso8601String(),
            ])
            ->all();

        $assignments = StudentAssignment::query()
            ->with(['student:id,name,email', 'course:id,name', 'level:id,name', 'unit:id,name'])
            ->orderBy('id')
            ->get()
            ->map(fn (StudentAssignment $assignment): array => [
                'id' => $assignment->id,
                'student_id' => $assignment->student_id,
                'student_email' => $assignment->student->email,
                'course' => $assignment->course->name,
                'level' => $assignment->level->name,
                'unit' => $assignment->unit->name,
                'assigned_by' => $assignment->assigned_by,
            ])
            ->all();

        $this->addJsonAndCsv($zip, 'students/students', $students);
        $this->addJsonAndCsv($zip, 'students/assignments', $assignments);
    }

    private function addTutor(ZipArchive $zip): void
    {
        $zip->addFromString('tutor/conversations.json', $this->toJson(TutorConversation::query()->orderBy('id')->get()->toArray()));
        $zip->addFromString('tutor/messages.json', $this->toJson(TutorMessage::query()->orderBy('id')->get()->toArray()));
        $zip->addFromString('tutor/violations.json', $this->toJson(TutorViolation::query()->orderBy('id')->get()->toArray()));
        $zip->addFromString('tutor/writing-submissions.json', $this->toJson(WritingSubmission::query()->orderBy('id')->get()->toArray()));

        if (config('glc.tutor.progress_analytics_enabled', false)) {
            $zip->addFromString('tutor/usage-daily.json', $this->toJson(TutorUsageDaily::query()->orderBy('id')->get()->toArray()));
            $zip->addFromString('tutor/progress-reports.json', $this->toJson(TutorProgressReport::query()->orderBy('id')->get()->toArray()));
        }
    }

    private function addAudit(ZipArchive $zip): void
    {
        $rows = AuditLog::query()
            ->with('actor:id,name,email')
            ->orderBy('id')
            ->get()
            ->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'created_at' => $log->created_at->toIso8601String(),
                'actor_id' => $log->actor_id,
                'actor_email' => $log->actor?->email,
                'action' => $log->action->value,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'details' => $log->details,
            ])
            ->all();

        $zip->addFromString('audit/audit-log.csv', $this->toCsv($rows));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function addJsonAndCsv(ZipArchive $zip, string $basePath, array $rows): void
    {
        $zip->addFromString($basePath.'.json', $this->toJson($rows));
        $zip->addFromString($basePath.'.csv', $this->toCsv($rows));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function toJson(array $rows): string
    {
        return json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function toCsv(array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $stream = fopen('php://temp', 'r+b');

        if ($stream === false) {
            throw new RuntimeException('Could not open a temporary stream for CSV generation.');
        }

        fputcsv($stream, array_keys($rows[0]));

        foreach ($rows as $row) {
            fputcsv($stream, array_map($this->toCsvValue(...), array_values($row)));
        }

        rewind($stream);
        $csv = (string) stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }

    private function toCsvValue(mixed $value): string
    {
        if (is_array($value)) {
            return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }
}
