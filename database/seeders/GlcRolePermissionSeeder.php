<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Glc\Admin\GlcRolePermissionRegistry;
use App\Services\Glc\Admin\SyncUserSpatieRole;
use Illuminate\Database\Seeder;

final class GlcRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(GlcRolePermissionRegistry::class)->installDefaultsIfMissing();
        app(SyncUserSpatieRole::class)->syncAllGlcUsers();
    }
}
