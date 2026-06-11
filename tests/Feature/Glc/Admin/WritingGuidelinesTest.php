<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\SettingKey;
use App\Models\Glc\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Glc\Admin\WritingEvaluationGuidelines;

beforeEach(function (): void {
    $this->withoutVite();
});

it('redirects guests and blocks non-admin roles', function (): void {
    $this->get(route('admin.settings.writing-guidelines.edit'))->assertRedirectToRoute('login');

    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)->get(route('admin.settings.writing-guidelines.edit'))->assertForbidden();
    $this->actingAs($teacher)->put(route('admin.settings.writing-guidelines.update'))->assertForbidden();
    $this->actingAs($teacher)->delete(route('admin.settings.writing-guidelines.reset'))->assertForbidden();

    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('admin.settings.writing-guidelines.edit'))->assertForbidden();
});

it('shows the default criteria when nothing is customized', function (): void {
    $admin = User::factory()->admin()->create();
    $defaults = app(WritingEvaluationGuidelines::class)->defaults();

    $this->actingAs($admin)
        ->get(route('admin.settings.writing-guidelines.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/admin/settings/writing-guidelines')
            ->where('criteria', $defaults)
            ->where('defaults', $defaults)
            ->where('isCustomized', false)
            ->where('limits', [
                'max_criteria' => WritingEvaluationGuidelines::MAX_CRITERIA,
                'max_title_length' => WritingEvaluationGuidelines::MAX_TITLE_LENGTH,
                'max_description_length' => WritingEvaluationGuidelines::MAX_DESCRIPTION_LENGTH,
            ])
        );
});

it('persists updated criteria and writes an audit log row', function (): void {
    $admin = User::factory()->admin()->create();

    $criteria = [
        ['title' => 'Spelling', 'description' => 'Words are spelled correctly throughout.'],
        ['title' => 'Punctuation', 'description' => 'Sentences are punctuated accurately.'],
    ];

    $this->actingAs($admin)
        ->put(route('admin.settings.writing-guidelines.update'), ['criteria' => $criteria])
        ->assertRedirectToRoute('admin.settings.writing-guidelines.edit')
        ->assertSessionHas('glc_status');

    $guidelines = app(WritingEvaluationGuidelines::class);

    expect($guidelines->effective())->toBe($criteria)
        ->and($guidelines->isCustomized())->toBeTrue();

    $log = AuditLog::query()->where('action', AuditAction::SettingsUpdated)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->details['writing_guidelines'])->toBe([
            'action' => 'updated',
            'criteria_count' => 2,
            'titles' => ['Spelling', 'Punctuation'],
        ]);
});

it('rejects invalid criteria payloads', function (array $payload, string $errorKey): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.writing-guidelines.update'), $payload)
        ->assertSessionHasErrors($errorKey);

    expect(Setting::get(SettingKey::GlcWritingGuidelines))->toBeNull();
})->with([
    'missing criteria' => [
        [],
        'criteria',
    ],
    'empty list' => [
        ['criteria' => []],
        'criteria',
    ],
    'missing title' => [
        ['criteria' => [['description' => 'No title given.']]],
        'criteria.0.title',
    ],
    'missing description' => [
        ['criteria' => [['title' => 'No description']]],
        'criteria.0.description',
    ],
    'too many criteria' => [
        ['criteria' => array_fill(0, WritingEvaluationGuidelines::MAX_CRITERIA + 1, ['title' => 'T', 'description' => 'D'])],
        'criteria',
    ],
    'overlong title' => [
        ['criteria' => [['title' => str_repeat('a', WritingEvaluationGuidelines::MAX_TITLE_LENGTH + 1), 'description' => 'Fine.']]],
        'criteria.0.title',
    ],
    'overlong description' => [
        ['criteria' => [['title' => 'Fine', 'description' => str_repeat('a', WritingEvaluationGuidelines::MAX_DESCRIPTION_LENGTH + 1)]]],
        'criteria.0.description',
    ],
]);

it('resets customized criteria back to defaults and audit-logs the reset', function (): void {
    $admin = User::factory()->admin()->create();
    $guidelines = app(WritingEvaluationGuidelines::class);

    $guidelines->update([
        ['title' => 'Custom criterion', 'description' => 'Something custom.'],
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.settings.writing-guidelines.reset'))
        ->assertRedirectToRoute('admin.settings.writing-guidelines.edit')
        ->assertSessionHas('glc_status');

    expect($guidelines->isCustomized())->toBeFalse()
        ->and($guidelines->effective())->toBe($guidelines->defaults());

    $log = AuditLog::query()->where('action', AuditAction::SettingsUpdated)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->details['writing_guidelines']['action'])->toBe('reset_to_defaults')
        ->and($log->details['writing_guidelines']['criteria_count'])->toBe(count($guidelines->defaults()));
});
