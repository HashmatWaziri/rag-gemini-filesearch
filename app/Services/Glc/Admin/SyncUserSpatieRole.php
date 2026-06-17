<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\Glc\UserRole;
use App\Models\User;

final readonly class SyncUserSpatieRole
{
    public function __construct(private GlcRolePermissionRegistry $registry) {}

    public function sync(User $user): void
    {
        $this->registry->installDefaultsIfMissing();

        if (! $user->role instanceof UserRole) {
            $user->syncRoles([]);

            return;
        }

        $user->syncRoles([$user->role->value]);
    }

    public function syncAllGlcUsers(): void
    {
        $this->registry->ensureInstalled();

        User::query()
            ->whereNotNull('role')
            ->each(fn (User $user): bool => (bool) $this->sync($user));
    }
}
