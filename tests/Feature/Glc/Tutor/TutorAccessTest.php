<?php

declare(strict_types=1);

use App\Models\Glc\TutorConversation;
use App\Models\User;
use Tests\Fixtures\Glc\TutorScenario;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
});

it('redirects unauthenticated users to login', function (): void {
    $this->get(route('tutor.index'))->assertRedirect(route('login'));
});

it('blocks non-student roles from the tutor', function (): void {
    $teacher = User::factory()->teacher()->create();

    actingAs($teacher)->get(route('tutor.index'))->assertForbidden();
});

it('shows the assignment prompt instead of chat for unassigned students', function (): void {
    $student = User::factory()->student()->create();

    actingAs($student)
        ->get(route('tutor.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/index')
            ->where('assignment', null));
});

it('does not create conversations for unassigned students', function (): void {
    $student = User::factory()->student()->create();

    actingAs($student)
        ->post(route('tutor.conversations.store'))
        ->assertRedirect(route('tutor.index'));

    expect(TutorConversation::query()->count())->toBe(0);
});

it('tells assigned students when their study materials are not ready yet', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent(withMaterials: false);

    actingAs($student)
        ->get(route('tutor.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/index')
            ->whereNot('assignment', null)
            ->where('materialsReady', false));
});

it('does not create conversations while no study materials are published', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent(withMaterials: false);

    actingAs($student)
        ->post(route('tutor.conversations.store'))
        ->assertRedirect(route('tutor.index'));

    expect(TutorConversation::query()->count())->toBe(0);
});

it('marks materials readiness on the chat screen', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent();
    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)
        ->get(route('tutor.conversations.show', $conversation))
        ->assertInertia(fn ($page) => $page->where('materialsReady', true));
});

it('flags the chat screen when materials become unavailable for an existing conversation', function (): void {
    ['student' => $student] = TutorScenario::assignedStudent(withMaterials: false);
    $conversation = TutorConversation::factory()->create(['user_id' => $student->id]);

    actingAs($student)
        ->get(route('tutor.conversations.show', $conversation))
        ->assertInertia(fn ($page) => $page->where('materialsReady', false));
});

it('redirects unconsented minors to the blocked page', function (): void {
    $minor = User::factory()->minorStudent()->create();

    actingAs($minor)
        ->get(route('tutor.index'))
        ->assertRedirect(route('tutor.blocked'));
});

it('renders the blocked page without a consent gate', function (): void {
    $minor = User::factory()->minorStudent()->create();

    actingAs($minor)
        ->get(route('tutor.blocked'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('glc/tutor/blocked'));
});

it('lets consented minors use the tutor', function (): void {
    $minor = User::factory()->minorStudent()->withGuardianConsent()->create();
    TutorScenario::assignedStudent(student: $minor);

    actingAs($minor)
        ->get(route('tutor.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/index')
            ->whereNot('assignment', null));
});

it('shows assignment scope and conversation list to assigned students', function (): void {
    ['student' => $student, 'course' => $course, 'level' => $level, 'unit' => $unit] = TutorScenario::assignedStudent();

    TutorConversation::factory()->count(2)->create(['user_id' => $student->id]);

    actingAs($student)
        ->get(route('tutor.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/tutor/index')
            ->where('assignment.course', $course->name)
            ->where('assignment.level', $level->name)
            ->where('assignment.unit', $unit->name)
            ->has('conversations', 2));
});
