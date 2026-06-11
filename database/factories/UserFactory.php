<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Glc\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    private static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
            'is_verified' => false,
            'locale' => 'en',
            'accepted_disclaimer_at' => now(),
            'role' => null,
            'age' => null,
            'guardian_name' => null,
            'guardian_email' => null,
            'guardian_consent_confirmed_at' => null,
            'guardian_consent_confirmed_by' => null,
        ];
    }

    public function withoutDisclaimer(): self
    {
        return $this->state(fn (array $attributes): array => [
            'accepted_disclaimer_at' => null,
        ]);
    }

    public function unverified(): self
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function withoutTwoFactor(): self
    {
        return $this->state(fn (array $attributes): array => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function verified(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_verified' => true,
        ]);
    }

    public function admin(): self
    {
        return $this->state(fn (): array => ['role' => UserRole::Admin]);
    }

    public function academicSupervisor(): self
    {
        return $this->state(fn (): array => ['role' => UserRole::AcademicSupervisor]);
    }

    public function teacher(): self
    {
        return $this->state(fn (): array => ['role' => UserRole::Teacher]);
    }

    public function student(): self
    {
        return $this->state(fn (): array => [
            'role' => UserRole::Student,
            'age' => 20,
        ]);
    }

    public function minorStudent(): self
    {
        return $this->state(fn (): array => [
            'role' => UserRole::Student,
            'age' => fake()->numberBetween(12, 17),
            'guardian_name' => fake()->name(),
            'guardian_email' => fake()->safeEmail(),
            'guardian_consent_confirmed_at' => null,
        ]);
    }

    public function withGuardianConsent(): self
    {
        return $this->state(fn (): array => [
            'guardian_consent_confirmed_at' => now(),
        ]);
    }
}
