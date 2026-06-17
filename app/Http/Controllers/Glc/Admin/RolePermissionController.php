<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\UserRole;
use App\Services\Glc\Admin\GlcRolePermissionRegistry;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class RolePermissionController
{
    public function __construct(
        private GlcRolePermissionRegistry $registry,
        private AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): Response
    {
        $permissions = $this->registry->permissions();
        $roles = $this->registry->roles();
        $matrix = $this->registry->currentMatrix();

        return Inertia::render('glc/admin/permissions/index', [
            'permissions' => $permissions,
            'roles' => $roles,
            'matrix' => $matrix,
            'status' => $request->session()->get('glc_status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $permissionKeys = collect($this->registry->permissions())->pluck('key')->all();
        $roleKeys = collect(UserRole::cases())->map(fn (UserRole $role): string => $role->value)->all();

        $validated = $request->validate([
            'matrix' => ['required', 'array'],
            'matrix.*' => ['array'],
            'matrix.*.*' => ['boolean'],
        ]);

        /** @var array<string, array<string, bool>> $submitted */
        $submitted = $validated['matrix'];

        $normalized = [];

        foreach ($roleKeys as $roleKey) {
            $row = $submitted[$roleKey] ?? [];

            $normalized[$roleKey] = collect($row)
                ->filter(fn (mixed $granted): bool => $granted === true)
                ->keys()
                ->filter(fn (mixed $permission): bool => is_string($permission) && in_array($permission, $permissionKeys, true))
                ->values()
                ->all();
        }

        $previous = $this->registry->currentMatrix();
        $this->registry->syncMatrix($normalized);

        $this->auditLogger->log(AuditAction::PermissionsUpdated, $request->user(), null, [
            'previous' => $previous,
            'current' => $normalized,
        ]);

        return to_route('admin.permissions.index')->with('glc_status', 'Role permissions updated.');
    }
}
