<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Models\Glc\TutorConversation;
use App\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
});

it('anonymizes a student while keeping records, and audits it', function (): void {
    $admin = User::factory()->admin()->create();
    $minor = User::factory()->minorStudent()->create([
        'name' => 'Real Name',
        'email' => 'real@glc.test',
        'age' => 15,
    ]);
    $conversation = TutorConversation::factory()->create(['user_id' => $minor->id]);

    $this->actingAs($admin)
        ->post(route('admin.users.anonymize', $minor))
        ->assertRedirectToRoute('admin.users.edit', $minor);

    $minor->refresh();

    expect($minor->name)->toBe('Anonymized Student')
        ->and($minor->email)->toBe("anonymized-{$minor->id}@redacted.invalid")
        ->and($minor->guardian_name)->toBe('Redacted Guardian')
        ->and($minor->guardian_email)->toBe("redacted-guardian-{$minor->id}@redacted.invalid")
        ->and($minor->age)->toBe(15);

    expect(TutorConversation::query()->find($conversation->id)?->user_id)->toBe($minor->id);

    $log = AuditLog::query()->where('action', AuditAction::UserAnonymized)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->subject_type)->toBe(User::class)
        ->and($log->subject_id)->toBe($minor->id)
        ->and($log->created_at)->not->toBeNull()
        ->and($log->details['fields'])->toContain('name', 'email', 'guardian_name', 'guardian_email');
});

it('leaves guardian fields null when anonymizing an adult student', function (): void {
    $admin = User::factory()->admin()->create();
    $adult = User::factory()->student()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.anonymize', $adult))
        ->assertSessionHasNoErrors();

    $adult->refresh();

    expect($adult->name)->toBe('Anonymized Student')
        ->and($adult->guardian_name)->toBeNull()
        ->and($adult->guardian_email)->toBeNull();
});

it('rejects anonymizing non-student accounts', function (): void {
    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($admin)->post(route('admin.users.anonymize', $teacher))->assertNotFound();

    expect($teacher->refresh()->name)->not->toBe('Anonymized Student');
});

it('blocks non-admins from anonymizing', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();
    $student = User::factory()->student()->create();

    $this->actingAs($supervisor)->post(route('admin.users.anonymize', $student))->assertForbidden();
});
