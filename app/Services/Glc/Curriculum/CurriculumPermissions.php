<?php

declare(strict_types=1);

namespace App\Services\Glc\Curriculum;

use App\Enums\Glc\UserRole;
use App\Models\User;
use App\Services\Glc\Admin\GlcRolePermissionRegistry;

final class CurriculumPermissions
{
    public function can(User $user, CurriculumPermission $action): bool
    {
        app(GlcRolePermissionRegistry::class)->installDefaultsIfMissing();

        if (! $user->role instanceof UserRole) {
            return false;
        }

        return $user->hasPermissionTo(
            $action->spatieName(),
            GlcRolePermissionRegistry::GUARD,
        );
    }
}
