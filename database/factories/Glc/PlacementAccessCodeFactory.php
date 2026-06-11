<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\PlacementAccessCodeStatus;
use App\Models\Glc\PlacementAccessCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementAccessCode>
 */
final class PlacementAccessCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => PlacementAccessCode::generateCode(),
            'status' => PlacementAccessCodeStatus::Unused,
            'expires_at' => null,
            'issued_by' => null,
            'revoked_at' => null,
            'note' => null,
        ];
    }

    public function inProgress(): self
    {
        return $this->state(fn (): array => ['status' => PlacementAccessCodeStatus::InProgress]);
    }

    public function completed(): self
    {
        return $this->state(fn (): array => ['status' => PlacementAccessCodeStatus::Completed]);
    }

    public function revoked(): self
    {
        return $this->state(fn (): array => [
            'status' => PlacementAccessCodeStatus::Revoked,
            'revoked_at' => now(),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDay()]);
    }
}
