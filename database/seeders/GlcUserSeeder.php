<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Glc\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo GLC accounts for local development and E2E scripts.
 *
 * | Email               | Password    | Role                | Notes                        |
 * |---------------------|-------------|---------------------|------------------------------|
 * | admin@glc.test      | GlcDemo2026 | admin               | Full admin access            |
 * | supervisor@glc.test | GlcDemo2026 | academic_supervisor | Staff review access          |
 * | teacher@glc.test    | GlcDemo2026 | teacher             | Staff review access          |
 * | student@glc.test    | GlcDemo2026 | student (age 20)    | Adult student, tutor enabled |
 * | minor@glc.test      | GlcDemo2026 | student (age 14)    | Minor with guardian consent  |
 *
 * Run: php artisan db:seed --class=GlcUserSeeder
 */
final class GlcUserSeeder extends Seeder
{
    private const string DEMO_PASSWORD = 'GlcDemo2026';

    public function run(): void
    {
        $admin = $this->upsertUser(
            email: 'admin@glc.test',
            name: 'GLC Admin',
            role: UserRole::Admin,
        );

        $this->upsertUser(
            email: 'supervisor@glc.test',
            name: 'GLC Academic Supervisor',
            role: UserRole::AcademicSupervisor,
        );

        $this->upsertUser(
            email: 'teacher@glc.test',
            name: 'GLC Teacher',
            role: UserRole::Teacher,
        );

        $this->upsertUser(
            email: 'student@glc.test',
            name: 'GLC Adult Student',
            role: UserRole::Student,
            extra: ['age' => 20],
        );

        $this->upsertUser(
            email: 'minor@glc.test',
            name: 'GLC Minor Student',
            role: UserRole::Student,
            extra: [
                'age' => 14,
                'guardian_name' => 'Parent Demo',
                'guardian_email' => 'guardian@glc.test',
                'guardian_consent_confirmed_at' => now(),
                'guardian_consent_confirmed_by' => $admin->id,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function upsertUser(string $email, string $name, UserRole $role, array $extra = []): User
    {
        $attributes = [
            'name' => $name,
            'password' => self::DEMO_PASSWORD,
            'email_verified_at' => now(),
            'role' => $role,
            'accepted_disclaimer_at' => now(),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            ...$extra,
        ];

        $existing = User::query()->where('email', $email)->first();

        if ($existing instanceof User) {
            $existing->update($attributes);

            return $existing->refresh();
        }

        return User::factory()
            ->withoutTwoFactor()
            ->create([
                'email' => $email,
                ...$attributes,
            ]);
    }
}
