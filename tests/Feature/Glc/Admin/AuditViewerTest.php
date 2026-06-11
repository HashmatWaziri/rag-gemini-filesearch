<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
});

it('redirects guests and blocks non-admin roles', function (): void {
    $this->get(route('admin.audit.index'))->assertRedirectToRoute('login');

    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('admin.audit.index'))->assertForbidden();
});

it('lists audit entries with actor, action, subject, details, and timestamp', function (): void {
    $admin = User::factory()->admin()->create(['name' => 'Audit Admin']);
    $student = User::factory()->student()->create();

    AuditLog::factory()->create([
        'actor_id' => $admin->id,
        'action' => AuditAction::ConsentConfirmed,
        'subject_type' => User::class,
        'subject_id' => $student->id,
        'details' => ['guardian_email' => 'parent@glc.test'],
    ]);

    $this->actingAs($admin)
        ->get(route('admin.audit.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/admin/audit/index')
            ->has('logs.data', 1)
            ->where('logs.data.0.actor_name', 'Audit Admin')
            ->where('logs.data.0.action', 'consent_confirmed')
            ->where('logs.data.0.action_label', 'Confirmed guardian consent')
            ->where('logs.data.0.subject', 'User #'.$student->id)
            ->where('logs.data.0.details.guardian_email', 'parent@glc.test')
            ->where('logs.data.0.created_at', fn ($value) => $value !== null)
            ->has('actions'));
});

it('filters audit entries by action', function (): void {
    $admin = User::factory()->admin()->create();

    AuditLog::factory()->create(['actor_id' => $admin->id, 'action' => AuditAction::UserCreated]);
    AuditLog::factory()->create(['actor_id' => $admin->id, 'action' => AuditAction::UserDeleted]);
    AuditLog::factory()->create(['actor_id' => $admin->id, 'action' => AuditAction::UserDeleted]);

    $this->actingAs($admin)
        ->get(route('admin.audit.index', ['action' => 'user_deleted']))
        ->assertInertia(fn ($page) => $page
            ->has('logs.data', 2)
            ->where('logs.data.0.action', 'user_deleted')
            ->where('logs.data.1.action', 'user_deleted')
            ->where('filters.action', 'user_deleted'));

    $this->actingAs($admin)
        ->get(route('admin.audit.index', ['action' => 'nonsense']))
        ->assertInertia(fn ($page) => $page->has('logs.data', 3));
});

it('paginates the audit log', function (): void {
    $admin = User::factory()->admin()->create();

    AuditLog::factory()->count(30)->create(['actor_id' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('admin.audit.index'))
        ->assertInertia(fn ($page) => $page
            ->has('logs.data', 25)
            ->where('logs.total', 30));
});
