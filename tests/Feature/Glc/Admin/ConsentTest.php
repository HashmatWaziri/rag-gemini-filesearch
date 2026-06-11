<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
});

it('blocks tutor access until consent is confirmed, then unblocks it', function (): void {
    $admin = User::factory()->admin()->create();
    $minor = User::factory()->minorStudent()->create();

    expect($minor->requiresGuardianConsent())->toBeTrue()
        ->and($minor->canUseTutor())->toBeFalse();

    $this->actingAs($admin)
        ->from(route('admin.users.edit', $minor))
        ->post(route('admin.users.consent.store', $minor))
        ->assertRedirect(route('admin.users.edit', $minor));

    $minor->refresh();

    expect($minor->hasGuardianConsent())->toBeTrue()
        ->and($minor->guardian_consent_confirmed_at)->not->toBeNull()
        ->and($minor->guardian_consent_confirmed_by)->toBe($admin->id)
        ->and($minor->canUseTutor())->toBeTrue();

    $log = AuditLog::query()->where('action', AuditAction::ConsentConfirmed)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->subject_type)->toBe(User::class)
        ->and($log->subject_id)->toBe($minor->id)
        ->and($log->details)->toMatchArray([
            'guardian_name' => $minor->guardian_name,
            'guardian_email' => $minor->guardian_email,
        ]);
});

it('revokes consent and blocks the tutor again', function (): void {
    $admin = User::factory()->admin()->create();
    $minor = User::factory()->minorStudent()->withGuardianConsent()->create([
        'guardian_consent_confirmed_by' => $admin->id,
    ]);

    expect($minor->canUseTutor())->toBeTrue();

    $this->actingAs($admin)
        ->delete(route('admin.users.consent.destroy', $minor))
        ->assertRedirect();

    $minor->refresh();

    expect($minor->hasGuardianConsent())->toBeFalse()
        ->and($minor->guardian_consent_confirmed_by)->toBeNull()
        ->and($minor->canUseTutor())->toBeFalse();

    expect(AuditLog::query()->where('action', AuditAction::ConsentRevoked)->where('subject_id', $minor->id)->exists())
        ->toBeTrue();
});

it('rejects consent toggling on non-student accounts', function (): void {
    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($admin)->post(route('admin.users.consent.store', $teacher))->assertNotFound();
    $this->actingAs($admin)->delete(route('admin.users.consent.destroy', $teacher))->assertNotFound();
});

it('blocks non-admins from toggling consent', function (): void {
    $teacher = User::factory()->teacher()->create();
    $minor = User::factory()->minorStudent()->create();

    $this->actingAs($teacher)->post(route('admin.users.consent.store', $minor))->assertForbidden();
});

it('does not require consent for adult students to use the tutor', function (): void {
    $adult = User::factory()->student()->create();

    expect($adult->requiresGuardianConsent())->toBeFalse()
        ->and($adult->canUseTutor())->toBeTrue();
});
