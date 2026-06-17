<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment(['local', 'development'])) {
            $this->call(GlcUserSeeder::class);
        }

        $this->call(GlcPlacementContentSeeder::class);
        $this->call(GlcCurriculumSeeder::class);
        $this->call(GlcRolePermissionSeeder::class);
    }
}
