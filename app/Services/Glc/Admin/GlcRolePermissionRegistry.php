<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\Glc\UserRole;
use App\Services\Glc\Curriculum\CurriculumPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class GlcRolePermissionRegistry
{
    public const string GUARD = 'web';

    /**
     * @return list<array{key: string, label: string, group: string, group_label: string}>
     */
    public function permissions(): array
    {
        return array_map(
            fn (CurriculumPermission $permission): array => [
                'key' => $permission->spatieName(),
                'label' => $permission->label(),
                'group' => 'curriculum',
                'group_label' => 'Curriculum',
            ],
            CurriculumPermission::cases(),
        );
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    public function roles(): array
    {
        return array_map(
            fn (UserRole $role): array => [
                'key' => $role->value,
                'label' => $role->label(),
            ],
            UserRole::cases(),
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public function defaultRolePermissions(): array
    {
        $staff = [UserRole::Admin->value, UserRole::AcademicSupervisor->value];

        $defaults = [];

        foreach (CurriculumPermission::cases() as $permission) {
            $roles = $permission === CurriculumPermission::Delete
                ? [UserRole::Admin->value]
                : $staff;

            foreach ($roles as $role) {
                $defaults[$role][] = $permission->spatieName();
            }
        }

        return $defaults;
    }

    public function ensureInstalled(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions() as $permission) {
            Permission::findOrCreate($permission['key'], self::GUARD);
        }

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, self::GUARD);
        }
    }

    public function installDefaultsIfMissing(): void
    {
        $this->ensureInstalled();

        /** @var Role|null $adminRole */
        $adminRole = Role::query()
            ->where('name', UserRole::Admin->value)
            ->where('guard_name', self::GUARD)
            ->first();

        if ($adminRole !== null && $adminRole->permissions()->count() > 0) {
            return;
        }

        $this->applyDefaults();
    }

    /**
     * @return array<string, list<string>>
     */
    public function currentMatrix(): array
    {
        $this->installDefaultsIfMissing();

        $matrix = [];

        foreach (UserRole::cases() as $role) {
            /** @var Role $roleModel */
            $roleModel = Role::findByName($role->value, self::GUARD);

            $matrix[$role->value] = $roleModel->permissions
                ->pluck('name')
                ->values()
                ->all();
        }

        return $matrix;
    }

    /**
     * @param  array<string, list<string>>  $matrix
     */
    public function syncMatrix(array $matrix): void
    {
        $this->ensureInstalled();

        $allowed = collect($this->permissions())->pluck('key')->all();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (UserRole::cases() as $role) {
            $granted = array_values(array_intersect($matrix[$role->value] ?? [], $allowed));

            /** @var Role $roleModel */
            $roleModel = Role::findByName($role->value, self::GUARD);
            $roleModel->syncPermissions($granted);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function applyDefaults(): void
    {
        $this->syncMatrix($this->defaultRolePermissions());
    }
}
