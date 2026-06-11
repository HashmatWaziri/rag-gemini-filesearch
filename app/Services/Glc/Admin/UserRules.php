<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\Glc\UserRole;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UserRules
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, list<mixed>>
     */
    public static function rules(array $input, ?int $ignoreUserId = null, bool $creating = true): array
    {
        $age = is_numeric($input['age'] ?? null) ? (int) $input['age'] : null;

        $needsGuardian = $age !== null
            && $age >= config()->integer('glc.guardian_consent.min_age', 12)
            && $age <= config()->integer('glc.guardian_consent.max_age', 17);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($ignoreUserId)],
            'password' => $creating
                ? ['required', 'string', Password::defaults()]
                : ['nullable', 'string', Password::defaults()],
            'role' => ['required', 'string', Rule::enum(UserRole::class)],
            'age' => ['nullable', 'integer', 'between:5,100'],
            'guardian_name' => $needsGuardian
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'guardian_email' => $needsGuardian
                ? ['required', 'string', 'email', 'max:255']
                : ['nullable', 'string', 'email', 'max:255'],
        ];
    }
}
