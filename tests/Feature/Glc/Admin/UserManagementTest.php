<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\UserRole;
use App\Models\Glc\AuditLog;
use App\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
});

it('redirects guests to login', function (): void {
    $this->get(route('admin.users.index'))->assertRedirectToRoute('login');
});

it('denies non-admin GLC roles with 403', function (string $factoryState): void {
    $user = User::factory()->{$factoryState}()->create();

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
})->with(['academicSupervisor', 'teacher', 'student']);

it('denies users without a GLC role', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
});

it('lists only GLC users with role filter and search', function (): void {
    $admin = User::factory()->admin()->create(['name' => 'Admin One']);
    $teacher = User::factory()->teacher()->create(['name' => 'Teacher Tan', 'email' => 'tan@glc.test']);
    User::factory()->student()->create(['name' => 'Student Lim']);
    User::factory()->create(['name' => 'Non GLC Platform User']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/admin/users/index')
            ->has('users.data', 3)
            ->where('privacyNotice', fn ($notice) => str_contains((string) $notice, 'PDPA')));

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['role' => 'teacher']))
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.email', 'tan@glc.test'));

    $this->actingAs($admin)
        ->get(route('admin.users.index', ['search' => 'Lim']))
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Student Lim'));
});

it('creates a staff account with one role and a verified email', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'New Teacher',
            'email' => 'teacher@glc.test',
            'password' => 'secret-password-123',
            'role' => UserRole::Teacher->value,
            'age' => 30,
        ])
        ->assertRedirectToRoute('admin.users.index');

    $user = User::query()->where('email', 'teacher@glc.test')->firstOrFail();

    expect($user->role)->toBe(UserRole::Teacher)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->age)->toBe(30);

    $log = AuditLog::query()->where('action', AuditAction::UserCreated)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->subject_id)->toBe($user->id)
        ->and($log->details)->toMatchArray(['email' => 'teacher@glc.test', 'role' => 'teacher']);
});

it('rejects creation without a valid single role', function (): void {
    $admin = User::factory()->admin()->create();

    $payload = [
        'name' => 'No Role',
        'email' => 'norole@glc.test',
        'password' => 'secret-password-123',
        'age' => 30,
    ];

    $this->actingAs($admin)
        ->post(route('admin.users.store'), $payload)
        ->assertSessionHasErrors('role');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [...$payload, 'role' => 'superuser'])
        ->assertSessionHasErrors('role');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [...$payload, 'role' => ['admin', 'teacher']])
        ->assertSessionHasErrors('role');

    expect(User::query()->where('email', 'norole@glc.test')->exists())->toBeFalse();
});

it('requires guardian fields for students aged 12 to 17', function (int $age): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Minor Student',
            'email' => 'minor@glc.test',
            'password' => 'secret-password-123',
            'role' => UserRole::Student->value,
            'age' => $age,
        ])
        ->assertSessionHasErrors(['guardian_name', 'guardian_email']);
})->with([12, 14, 17]);

it('captures guardian fields for minors and skips them for adults', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Minor Student',
            'email' => 'minor@glc.test',
            'password' => 'secret-password-123',
            'role' => UserRole::Student->value,
            'age' => 14,
            'guardian_name' => 'Parent Lee',
            'guardian_email' => 'parent@glc.test',
        ])
        ->assertSessionHasNoErrors();

    $minor = User::query()->where('email', 'minor@glc.test')->firstOrFail();

    expect($minor->guardian_name)->toBe('Parent Lee')
        ->and($minor->guardian_email)->toBe('parent@glc.test')
        ->and($minor->requiresGuardianConsent())->toBeTrue()
        ->and($minor->hasGuardianConsent())->toBeFalse();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Adult Student',
            'email' => 'adult@glc.test',
            'password' => 'secret-password-123',
            'role' => UserRole::Student->value,
            'age' => 20,
        ])
        ->assertSessionHasNoErrors();

    expect(User::query()->where('email', 'adult@glc.test')->firstOrFail()->requiresGuardianConsent())->toBeFalse();
});

it('shows the edit page with the privacy notice', function (): void {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->minorStudent()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.edit', $student))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/admin/users/edit')
            ->where('user.id', $student->id)
            ->where('user.requires_guardian_consent', true)
            ->where('privacyNotice', fn ($notice) => str_contains((string) $notice, 'PDPA')));
});

it('returns 404 when editing a non-GLC user', function (): void {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();

    $this->actingAs($admin)->get(route('admin.users.edit', $other))->assertNotFound();
});

it('updates a user and audits the changed fields', function (): void {
    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->teacher()->create(['age' => 30]);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $teacher), [
            'name' => 'Renamed Teacher',
            'email' => $teacher->email,
            'role' => UserRole::AcademicSupervisor->value,
            'age' => 30,
        ])
        ->assertRedirectToRoute('admin.users.edit', $teacher);

    $teacher->refresh();

    expect($teacher->name)->toBe('Renamed Teacher')
        ->and($teacher->role)->toBe(UserRole::AcademicSupervisor);

    $log = AuditLog::query()->where('action', AuditAction::UserUpdated)->firstOrFail();

    expect($log->subject_id)->toBe($teacher->id)
        ->and($log->details['fields'])->toContain('name', 'role');
});

it('keeps the password when left blank on update', function (): void {
    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->teacher()->create();
    $originalPassword = $teacher->password;

    $this->actingAs($admin)
        ->put(route('admin.users.update', $teacher), [
            'name' => $teacher->name,
            'email' => $teacher->email,
            'role' => UserRole::Teacher->value,
        ])
        ->assertSessionHasNoErrors();

    expect($teacher->refresh()->password)->toBe($originalPassword);
});

it('deletes a user and audits who, when, and what', function (): void {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create(['name' => 'Going Away', 'email' => 'away@glc.test']);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $student))
        ->assertRedirectToRoute('admin.users.index');

    expect(User::query()->find($student->id))->toBeNull();

    $log = AuditLog::query()->where('action', AuditAction::UserDeleted)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->subject_type)->toBe(User::class)
        ->and($log->subject_id)->toBe($student->id)
        ->and($log->created_at)->not->toBeNull()
        ->and($log->details)->toMatchArray([
            'name' => 'Going Away',
            'email' => 'away@glc.test',
            'role' => 'student',
        ]);
});

it('prevents an admin from deleting their own account', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertSessionHasErrors('user');

    expect(User::query()->find($admin->id))->not->toBeNull();
});
