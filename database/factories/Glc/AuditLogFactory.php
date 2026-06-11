<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
final class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory()->admin(),
            'action' => AuditAction::UserCreated,
            'subject_type' => null,
            'subject_id' => null,
            'details' => null,
            'created_at' => now(),
        ];
    }
}
