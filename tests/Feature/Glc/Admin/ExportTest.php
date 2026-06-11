<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Models\Glc\Course;
use App\Models\Glc\CourseLesson;
use App\Models\Glc\CourseLevel;
use App\Models\Glc\CourseUnit;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementReview;
use App\Models\Glc\PlacementScore;
use App\Models\Glc\StudentAssignment;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\Glc\TutorViolation;
use App\Models\Glc\WritingSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

function openExportZip(TestResponse $response): ZipArchive
{
    $file = $response->baseResponse->getFile();

    $zip = new ZipArchive;

    expect($zip->open($file->getPathname()))->toBeTrue();

    return $zip;
}

beforeEach(function (): void {
    $this->withoutVite();
});

it('redirects guests and blocks non-admin roles', function (): void {
    $this->get(route('admin.exports.index'))->assertRedirectToRoute('login');

    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)->get(route('admin.exports.index'))->assertForbidden();
    $this->actingAs($teacher)->get(route('admin.exports.download', 'placement'))->assertForbidden();
});

it('shows the export bundles page', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.exports.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/admin/exports/index')
            ->has('bundles', 5)
            ->where('bundles.0.value', 'placement'));
});

it('returns 404 for an unknown bundle type', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.exports.download', 'everything'))->assertNotFound();
});

it('exports the placement bundle with JSON and CSV entries', function (): void {
    $admin = User::factory()->admin()->create();

    $item = PlacementItem::factory()->create();
    $attempt = PlacementAttempt::factory()->submitted()->create();
    PlacementAnswer::factory()->create([
        'placement_attempt_id' => $attempt->id,
        'placement_item_id' => $item->id,
    ]);
    PlacementScore::factory()->create(['placement_attempt_id' => $attempt->id]);
    PlacementReview::factory()->create(['placement_attempt_id' => $attempt->id]);

    $response = $this->actingAs($admin)
        ->get(route('admin.exports.download', 'placement'))
        ->assertOk()
        ->assertDownload();

    $zip = openExportZip($response);

    foreach (['items', 'attempts', 'answers', 'scores', 'reviews'] as $table) {
        expect($zip->locateName("placement/{$table}.json"))->not->toBeFalse()
            ->and($zip->locateName("placement/{$table}.csv"))->not->toBeFalse();
    }

    $attempts = json_decode((string) $zip->getFromName('placement/attempts.json'), true);

    expect($attempts)->toHaveCount(1)
        ->and($attempts[0]['candidate_email'])->toBe($attempt->candidate_email);

    $itemsCsv = (string) $zip->getFromName('placement/items.csv');

    expect($itemsCsv)->toContain('id')->not->toBe('');

    $zip->close();

    $log = AuditLog::query()->where('action', AuditAction::DataExported)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->details)->toMatchArray(['bundle' => 'placement']);
});

it('exports the curriculum bundle with documents, hierarchy, and files manifest', function (): void {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();

    $course = Course::factory()->create(['name' => 'General English']);
    $level = CourseLevel::factory()->create(['course_id' => $course->id, 'name' => 'Beginner']);
    $unit = CourseUnit::factory()->create(['course_level_id' => $level->id, 'name' => 'Unit 1']);
    CourseLesson::factory()->create(['course_unit_id' => $unit->id]);
    $document = CurriculumDocument::factory()->published()->create([
        'course_id' => $course->id,
        'course_level_id' => $level->id,
        'course_unit_id' => $unit->id,
        'course_lesson_id' => null,
        'extracted_text' => 'Plural nouns add -s in most cases.',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.exports.download', 'curriculum'))
        ->assertOk();

    $zip = openExportZip($response);

    $documents = json_decode((string) $zip->getFromName('curriculum/documents.json'), true);
    $hierarchy = json_decode((string) $zip->getFromName('curriculum/hierarchy.json'), true);
    $manifest = (string) $zip->getFromName('curriculum/files-manifest.csv');

    expect($documents[0]['file_path'])->toBe($document->file_path)
        ->and($hierarchy[0]['name'])->toBe('General English')
        ->and($hierarchy[0]['levels'][0]['units'][0]['lessons'])->toHaveCount(1)
        ->and($manifest)->toContain('storage_path')
        ->and($manifest)->toContain($document->file_path);

    $rows = array_map('str_getcsv', array_filter(explode("\n", mb_trim($manifest))));
    $row = array_combine($rows[0], $rows[1]);

    expect($row['id'])->toBe((string) $document->id)
        ->and($row['title'])->toBe($document->title)
        ->and($row['original_filename'])->toBe($document->original_filename)
        ->and($row['course'])->toBe('General English')
        ->and($row['level'])->toBe('Beginner')
        ->and($row['unit'])->toBe('Unit 1')
        ->and($row['lesson'])->toBe('Unit-wide')
        ->and($row['status'])->toBe('published')
        ->and($row['version'])->toBe('1')
        ->and($row['created_at'])->toBe($document->created_at->toIso8601String())
        ->and($row['updated_at'])->toBe($document->updated_at->toIso8601String())
        ->and($row['extracted_text_preview'])->toBe('Plural nouns add -s in most cases.')
        ->and($row['gemini_file_resource_name'])->toBe($document->gemini_file_name)
        ->and($row['gemini_sync_status'])->toBe('indexed');

    $zip->close();

    expect(AuditLog::query()->where('action', AuditAction::DataExported)->where('details->bundle', 'curriculum')->exists())
        ->toBeTrue();
});

it('names the lesson in the manifest when a document is lesson-specific', function (): void {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $unit = CourseUnit::factory()->create();
    $lesson = CourseLesson::factory()->create(['course_unit_id' => $unit->id, 'name' => 'Lesson 3']);
    CurriculumDocument::factory()->create([
        'course_unit_id' => $unit->id,
        'course_level_id' => $unit->course_level_id,
        'course_lesson_id' => $lesson->id,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.exports.download', 'curriculum'))
        ->assertOk();

    $zip = openExportZip($response);

    expect((string) $zip->getFromName('curriculum/files-manifest.csv'))->toContain('Lesson 3');

    $zip->close();
});

it('filters the curriculum bundle by lifecycle state and audits the choice', function (): void {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $draft = CurriculumDocument::factory()->create(['title' => 'Draft Worksheet']);
    $published = CurriculumDocument::factory()->published()->create(['title' => 'Published Worksheet']);
    CurriculumDocument::factory()->archived()->create(['title' => 'Archived Worksheet']);

    $response = $this->actingAs($admin)
        ->get(route('admin.exports.download', ['bundle' => 'curriculum', 'statuses' => ['published']]))
        ->assertOk();

    $zip = openExportZip($response);

    $documents = json_decode((string) $zip->getFromName('curriculum/documents.json'), true);
    $manifest = (string) $zip->getFromName('curriculum/files-manifest.csv');

    expect($documents)->toHaveCount(1)
        ->and($documents[0]['id'])->toBe($published->id)
        ->and($manifest)->toContain('Published Worksheet')
        ->and($manifest)->not->toContain('Draft Worksheet')
        ->and($manifest)->not->toContain('Archived Worksheet');

    $zip->close();

    $log = AuditLog::query()->where('action', AuditAction::DataExported)->firstOrFail();

    expect($log->details)->toMatchArray(['bundle' => 'curriculum', 'statuses' => ['published']])
        ->and(CurriculumDocument::query()->whereKey($draft->id)->exists())->toBeTrue();
});

it('rejects unknown lifecycle states for the curriculum bundle', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.exports.index'))
        ->get(route('admin.exports.download', ['bundle' => 'curriculum', 'statuses' => ['nonsense']]))
        ->assertRedirect(route('admin.exports.index'))
        ->assertSessionHasErrors('statuses.0');

    expect(AuditLog::query()->where('action', AuditAction::DataExported)->exists())->toBeFalse();
});

it('includes all lifecycle states when no filter is given', function (): void {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    CurriculumDocument::factory()->create();
    CurriculumDocument::factory()->published()->create();
    CurriculumDocument::factory()->archived()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.exports.download', 'curriculum'))
        ->assertOk();

    $zip = openExportZip($response);

    expect(json_decode((string) $zip->getFromName('curriculum/documents.json'), true))->toHaveCount(3);

    $zip->close();

    $log = AuditLog::query()->where('action', AuditAction::DataExported)->firstOrFail();

    expect($log->details)->toBe(['bundle' => 'curriculum']);
});

it('exports student records with consent state and assignments', function (): void {
    $admin = User::factory()->admin()->create();
    $minor = User::factory()->minorStudent()->withGuardianConsent()->create();
    $adult = User::factory()->student()->create();
    StudentAssignment::factory()->create(['student_id' => $adult->id]);

    $response = $this->actingAs($admin)
        ->get(route('admin.exports.download', 'students'))
        ->assertOk();

    $zip = openExportZip($response);

    $students = collect(json_decode((string) $zip->getFromName('students/students.json'), true));

    expect($students)->toHaveCount(2);

    $minorRow = $students->firstWhere('id', $minor->id);

    expect($minorRow['requires_guardian_consent'])->toBeTrue()
        ->and($minorRow['guardian_consent_confirmed'])->toBeTrue()
        ->and($minorRow['guardian_name'])->toBe($minor->guardian_name)
        ->and($minorRow)->not->toHaveKey('password');

    $assignments = json_decode((string) $zip->getFromName('students/assignments.json'), true);

    expect($assignments)->toHaveCount(1)
        ->and($assignments[0]['student_id'])->toBe($adult->id)
        ->and($assignments[0])->toHaveKeys(['course', 'level', 'unit']);

    expect($zip->locateName('students/students.csv'))->not->toBeFalse()
        ->and($zip->locateName('students/assignments.csv'))->not->toBeFalse();

    $zip->close();
});

it('exports tutor data with conversations, messages, violations, and writing submissions', function (): void {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();
    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);
    TutorMessage::factory()->create(['tutor_conversation_id' => $conversation->id, 'content' => 'Help me with grammar']);
    TutorViolation::factory()->create(['user_id' => $student->id, 'tutor_conversation_id' => $conversation->id]);
    WritingSubmission::factory()->create(['user_id' => $student->id]);

    $response = $this->actingAs($admin)
        ->get(route('admin.exports.download', 'tutor'))
        ->assertOk();

    $zip = openExportZip($response);

    $messages = json_decode((string) $zip->getFromName('tutor/messages.json'), true);

    expect($messages[0]['content'])->toBe('Help me with grammar')
        ->and(json_decode((string) $zip->getFromName('tutor/conversations.json'), true))->toHaveCount(1)
        ->and(json_decode((string) $zip->getFromName('tutor/violations.json'), true))->toHaveCount(1)
        ->and(json_decode((string) $zip->getFromName('tutor/writing-submissions.json'), true))->toHaveCount(1);

    $zip->close();
});

it('exports the audit log as CSV', function (): void {
    $admin = User::factory()->admin()->create();
    AuditLog::factory()->create([
        'actor_id' => $admin->id,
        'action' => AuditAction::ConsentConfirmed,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.exports.download', 'audit'))
        ->assertOk();

    $zip = openExportZip($response);

    $csv = (string) $zip->getFromName('audit/audit-log.csv');

    expect($csv)->toContain('actor_email')
        ->and($csv)->toContain('consent_confirmed')
        ->and($csv)->toContain($admin->email);

    $zip->close();
});
