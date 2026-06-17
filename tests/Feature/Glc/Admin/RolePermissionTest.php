<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Models\User;
use App\Services\Glc\Admin\GlcRolePermissionRegistry;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->withoutVite();
});

it('shows the role permission matrix to admins', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.permissions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/admin/permissions/index')
            ->has('permissions', 8)
            ->has('roles', 4)
            ->has('matrix'));
});

it('forbids non-admins from managing permissions', function (): void {
    $supervisor = User::factory()->academicSupervisor()->create();

    $this->actingAs($supervisor)
        ->get(route('admin.permissions.index'))
        ->assertForbidden();
});

it('updates role permissions and writes an audit log', function (): void {
    $admin = User::factory()->admin()->create();
    $registry = app(GlcRolePermissionRegistry::class);
    $permissionKeys = collect($registry->permissions())->pluck('key')->all();
    $roleKeys = collect($registry->roles())->pluck('key')->all();

    $matrix = $registry->currentMatrix();
    $matrix['teacher'] = ['curriculum_view'];

    $payload = [];

    foreach ($roleKeys as $roleKey) {
        $granted = $matrix[$roleKey] ?? [];

        foreach ($permissionKeys as $permissionKey) {
            $payload[$roleKey][$permissionKey] = in_array($permissionKey, $granted, true);
        }
    }

    $this->actingAs($admin)
        ->put(route('admin.permissions.update'), ['matrix' => $payload])
        ->assertRedirect(route('admin.permissions.index'));

    expect(Role::findByName('teacher', 'web')->hasPermissionTo('curriculum_view'))->toBeTrue()
        ->and(Role::findByName('teacher', 'web')->hasPermissionTo('curriculum_upload'))->toBeFalse();

    $audit = AuditLog::query()->where('action', AuditAction::PermissionsUpdated->value)->firstOrFail();

    expect($audit->actor_id)->toBe($admin->id)
        ->and($audit->details['current']['teacher'])->toBe(['curriculum_view']);
});

it('seeds default curriculum permissions for staff roles', function (): void {
    app(GlcRolePermissionRegistry::class)->installDefaultsIfMissing();

    expect(Role::findByName('academic_supervisor', 'web')->hasPermissionTo('curriculum_publish'))->toBeTrue()
        ->and(Role::findByName('academic_supervisor', 'web')->hasPermissionTo('curriculum_delete'))->toBeFalse()
        ->and(Role::findByName('admin', 'web')->hasPermissionTo('curriculum_delete'))->toBeTrue()
        ->and(Role::findByName('teacher', 'web')->permissions)->toHaveCount(0);
});
