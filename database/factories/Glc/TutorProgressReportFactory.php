<?php

declare(strict_types=1);

namespace Database\Factories\Glc;

use App\Models\Glc\TutorProgressReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TutorProgressReport>
 */
final class TutorProgressReportFactory extends Factory
{
    protected $model = TutorProgressReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'generated_by' => User::factory()->teacher(),
            'status' => 'completed',
            'period_start' => now()->subDays(30)->toDateString(),
            'period_end' => now()->toDateString(),
            'payload' => [
                'summary' => fake()->sentence(),
                'strengths' => [fake()->word()],
                'focus_areas' => [fake()->word()],
                'engagement_note' => fake()->sentence(),
            ],
            'error' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => 'pending',
            'payload' => null,
            'error' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
            'payload' => null,
            'error' => 'Could not generate the progress report.',
        ]);
    }
}
