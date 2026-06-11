<?php

declare(strict_types=1);

use App\Enums\Glc\TutorViolationCategory;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\Glc\TutorViolation;
use App\Models\Glc\WritingSubmission;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
});

it('scopes the activity roster to assigned students for teachers', function (): void {
    $teacher = User::factory()->teacher()->create();
    $mine = User::factory()->student()->create();
    User::factory()->student()->create();

    $teacher->assignedStudents()->attach($mine);

    actingAs($teacher)
        ->get(route('staff.tutor.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/staff/index')
            ->has('students', 1)
            ->where('students.0.id', $mine->id));
});

it('shows the full roster to supervisors and admins', function (string $factoryState): void {
    $staff = User::factory()->{$factoryState}()->create();
    User::factory()->student()->count(2)->create();

    actingAs($staff)
        ->get(route('staff.tutor.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('students', 2));
})->with(['academicSupervisor', 'admin']);

it('reports last active date and conversation count per student', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $student = User::factory()->student()->create();

    TutorConversation::factory()->create([
        'user_id' => $student->id,
        'last_activity_at' => now()->subDays(3),
    ]);
    TutorConversation::factory()->create([
        'user_id' => $student->id,
        'last_activity_at' => now()->subDay(),
    ]);

    actingAs($supervisor)
        ->get(route('staff.tutor.index'))
        ->assertInertia(fn ($page) => $page
            ->where('students.0.conversation_count', 2)
            ->where(
                'students.0.last_active_at',
                fn ($value): bool => str_starts_with((string) $value, now()->subDay()->format('Y-m-d')),
            ));
});

it('shows a student detail with conversations, writing, violations, and activity to the linked teacher', function (): void {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $teacher->assignedStudents()->attach($student);

    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);
    TutorMessage::factory()->count(2)->create(['tutor_conversation_id' => $conversation->id]);
    WritingSubmission::factory()->completed()->create(['user_id' => $student->id]);
    TutorViolation::factory()->category(TutorViolationCategory::SpeakingListeningRequest)->create([
        'user_id' => $student->id,
    ]);

    actingAs($teacher)
        ->get(route('staff.tutor.students.show', $student))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/staff/student')
            ->where('student.id', $student->id)
            ->where('activity.conversation_count', 1)
            ->has('conversations', 1)
            ->where('conversations.0.message_count', 2)
            ->has('writingSubmissions', 1)
            ->has('violations', 1)
            ->where('violations.0.category', 'speaking_listening_request')
            ->where('violations.0.category_label', 'Speaking / Listening Practice Request'));
});

it('blocks teachers from unassigned student details', function (): void {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    actingAs($teacher)->get(route('staff.tutor.students.show', $student))->assertForbidden();
});

it('returns 404 for a non-student account on the student detail', function (): void {
    $teacher = User::factory()->teacher()->create();
    $otherStaff = User::factory()->admin()->create();

    actingAs($teacher)->get(route('staff.tutor.students.show', $otherStaff))->assertNotFound();
});

it('lets supervisors and admins open any student detail', function (string $factoryState): void {
    $staff = User::factory()->{$factoryState}()->create();
    $student = User::factory()->student()->create();

    actingAs($staff)->get(route('staff.tutor.students.show', $student))->assertOk();
})->with(['academicSupervisor', 'admin']);

it('shows the full transcript including rotated messages', function (): void {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $teacher->assignedStudents()->attach($student);

    $conversation = TutorConversation::factory()->create([
        'user_id' => $student->id,
        'summary' => 'Earlier: practiced articles.',
    ]);

    TutorMessage::factory()->create([
        'tutor_conversation_id' => $conversation->id,
        'content' => 'Old question',
        'metadata' => ['rotated' => true],
    ]);
    TutorMessage::factory()->assistant()->create([
        'tutor_conversation_id' => $conversation->id,
        'content' => 'Recent answer',
        'metadata' => ['violation' => 'off_topic'],
    ]);

    actingAs($teacher)
        ->get(route('staff.tutor.conversations.show', $conversation))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/staff/conversation')
            ->where('conversation.summary', 'Earlier: practiced articles.')
            ->has('messages', 2)
            ->where('messages.0.content', 'Old question')
            ->where('messages.0.rotated', true)
            ->where('messages.1.rotated', false)
            ->where('messages.1.violation', 'off_topic'));
});

it('blocks teachers from transcripts of unassigned students', function (): void {
    $teacher = User::factory()->teacher()->create();
    $conversation = TutorConversation::factory()->create();

    actingAs($teacher)->get(route('staff.tutor.conversations.show', $conversation))->assertForbidden();
});

it('shows writing corrections of assigned students to teachers', function (): void {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $teacher->assignedStudents()->attach($student);

    $submission = WritingSubmission::factory()->completed()->create(['user_id' => $student->id]);

    actingAs($teacher)
        ->get(route('staff.tutor.writing.show', $submission))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/staff/writing')
            ->where('student.id', $student->id)
            ->has('submission.feedback.dimensions', 5));
});

it('blocks teachers from writing submissions of unassigned students', function (): void {
    $teacher = User::factory()->teacher()->create();
    $submission = WritingSubmission::factory()->create();

    actingAs($teacher)->get(route('staff.tutor.writing.show', $submission))->assertForbidden();
});

it('lets supervisors view any transcript and writing submission', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $conversation = TutorConversation::factory()->create();
    $submission = WritingSubmission::factory()->completed()->create();

    actingAs($supervisor)->get(route('staff.tutor.conversations.show', $conversation))->assertOk();
    actingAs($supervisor)->get(route('staff.tutor.writing.show', $submission))->assertOk();
});

it('blocks students from the staff tutor screens', function (): void {
    $student = User::factory()->student()->create();
    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)->get(route('staff.tutor.index'))->assertForbidden();
    actingAs($student)->get(route('staff.tutor.conversations.show', $conversation))->assertForbidden();
});
