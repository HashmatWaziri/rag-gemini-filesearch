<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Models\Glc\AuditLog;
use App\Models\User;
use App\Services\Glc\Admin\SpeakingEvaluationGuidelines;
use App\Services\Glc\Admin\WritingEvaluationGuidelines;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutVite();
});

/**
 * @return array{criteria: list<array{title: string, description: string}>}
 */
function criteriaPayload(int $count = 2): array
{
    return [
        'criteria' => array_map(fn (int $i): array => [
            'title' => "Criterion {$i}",
            'description' => "Description for criterion {$i}.",
        ], range(1, $count)),
    ];
}

it('lets supervisors update the marking criteria for both skills', function (string $skill, string $serviceClass): void {
    actingAs(User::factory()->academicSupervisor()->create())
        ->put(route('staff.content.criteria.update', $skill), criteriaPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $guidelines = app($serviceClass);

    expect($guidelines->isCustomized())->toBeTrue()
        ->and($guidelines->effective())->toBe([
            ['title' => 'Criterion 1', 'description' => 'Description for criterion 1.'],
            ['title' => 'Criterion 2', 'description' => 'Description for criterion 2.'],
        ]);

    $log = AuditLog::query()->where('action', AuditAction::SettingsUpdated)->firstOrFail();

    expect($log->details[$skill.'_guidelines'])->toBe([
        'action' => 'updated',
        'criteria_count' => 2,
        'titles' => ['Criterion 1', 'Criterion 2'],
    ]);
})->with([
    'writing' => ['writing', WritingEvaluationGuidelines::class],
    'speaking' => ['speaking', SpeakingEvaluationGuidelines::class],
]);

it('lets supervisors reset the marking criteria to the GLC defaults', function (): void {
    $guidelines = app(WritingEvaluationGuidelines::class);
    $guidelines->update([['title' => 'Custom', 'description' => 'Custom rubric.']]);

    actingAs(User::factory()->academicSupervisor()->create())
        ->delete(route('staff.content.criteria.reset', 'writing'))
        ->assertRedirect();

    expect($guidelines->isCustomized())->toBeFalse()
        ->and($guidelines->effective()[0]['title'])->toBe('Task achievement');

    $log = AuditLog::query()->where('action', AuditAction::SettingsUpdated)->latest('id')->firstOrFail();

    expect($log->details['writing_guidelines']['action'])->toBe('reset_to_defaults')
        ->and($log->details['writing_guidelines']['titles'])->toContain('Task achievement');
});

it('blocks teachers and students from managing marking criteria', function (): void {
    actingAs(User::factory()->teacher()->create())
        ->put(route('staff.content.criteria.update', 'writing'), criteriaPayload())
        ->assertForbidden();

    actingAs(User::factory()->student()->create())
        ->delete(route('staff.content.criteria.reset', 'speaking'))
        ->assertForbidden();
});

it('rejects unknown skills', function (): void {
    actingAs(User::factory()->admin()->create())
        ->put('/staff/placement-content/criteria/reading', criteriaPayload())
        ->assertNotFound();
});

it('validates criteria payload limits', function (array $payload, string $errorField): void {
    actingAs(User::factory()->admin()->create())
        ->put(route('staff.content.criteria.update', 'speaking'), $payload)
        ->assertSessionHasErrors($errorField);
})->with([
    'no criteria' => [['criteria' => []], 'criteria'],
    'more than twenty criteria' => [criteriaPayload(21), 'criteria'],
    'a missing title' => [['criteria' => [['title' => '', 'description' => 'd']]], 'criteria.0.title'],
    'an over-long title' => [['criteria' => [['title' => str_repeat('a', 121), 'description' => 'd']]], 'criteria.0.title'],
    'an over-long description' => [['criteria' => [['title' => 't', 'description' => str_repeat('a', 1001)]]], 'criteria.0.description'],
]);

it('feeds staff-saved criteria into the same guidelines the AI prompts use', function (): void {
    actingAs(User::factory()->admin()->create())
        ->put(route('staff.content.criteria.update', 'writing'), [
            'criteria' => [['title' => 'Idiomatic range', 'description' => 'Natural use of idioms.']],
        ])
        ->assertRedirect();

    expect(app(WritingEvaluationGuidelines::class)->asPromptBlock())
        ->toBe('1. Idiomatic range: Natural use of idioms.');
});
