<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\SettingKey;
use App\Models\Glc\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Glc\Admin\SpeakingEvaluationGuidelines;

beforeEach(function (): void {
    $this->withoutVite();
});

it('redirects guests and blocks non-admin roles', function (): void {
    $this->get(route('admin.settings.speaking-guidelines.edit'))->assertRedirectToRoute('login');

    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)->get(route('admin.settings.speaking-guidelines.edit'))->assertForbidden();
    $this->actingAs($teacher)->put(route('admin.settings.speaking-guidelines.update'))->assertForbidden();
    $this->actingAs($teacher)->delete(route('admin.settings.speaking-guidelines.reset'))->assertForbidden();
});

it('shows the default speaking criteria when nothing is customized', function (): void {
    $admin = User::factory()->admin()->create();
    $defaults = app(SpeakingEvaluationGuidelines::class)->defaults();

    expect(array_column($defaults, 'title'))->toContain('Fluency and coherence', 'Comprehensibility');

    $this->actingAs($admin)
        ->get(route('admin.settings.speaking-guidelines.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/admin/settings/speaking-guidelines')
            ->where('criteria', $defaults)
            ->where('defaults', $defaults)
            ->where('isCustomized', false)
            ->where('limits.max_criteria', SpeakingEvaluationGuidelines::MAX_CRITERIA)
        );
});

it('persists updated speaking criteria independently of the writing guidelines and audit-logs the change', function (): void {
    $admin = User::factory()->admin()->create();

    $criteria = [
        ['title' => 'Pace', 'description' => 'Speaks at a natural, steady pace.'],
        ['title' => 'Detail', 'description' => 'Develops answers with supporting detail.'],
    ];

    $this->actingAs($admin)
        ->put(route('admin.settings.speaking-guidelines.update'), ['criteria' => $criteria])
        ->assertRedirectToRoute('admin.settings.speaking-guidelines.edit')
        ->assertSessionHas('glc_status');

    $guidelines = app(SpeakingEvaluationGuidelines::class);

    expect($guidelines->effective())->toBe($criteria)
        ->and($guidelines->isCustomized())->toBeTrue()
        ->and(Setting::get(SettingKey::GlcWritingGuidelines))->toBeNull();

    $log = AuditLog::query()->where('action', AuditAction::SettingsUpdated)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->details['speaking_guidelines'])->toBe([
            'action' => 'updated',
            'criteria_count' => 2,
            'titles' => ['Pace', 'Detail'],
        ]);
});

it('rejects invalid speaking criteria payloads', function (array $payload, string $errorKey): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.speaking-guidelines.update'), $payload)
        ->assertSessionHasErrors($errorKey);

    expect(Setting::get(SettingKey::GlcSpeakingGuidelines))->toBeNull();
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
    'overlong title' => [
        ['criteria' => [['title' => str_repeat('a', SpeakingEvaluationGuidelines::MAX_TITLE_LENGTH + 1), 'description' => 'Fine.']]],
        'criteria.0.title',
    ],
]);

it('resets customized speaking criteria back to defaults and audit-logs the reset', function (): void {
    $admin = User::factory()->admin()->create();
    $guidelines = app(SpeakingEvaluationGuidelines::class);

    $guidelines->update([
        ['title' => 'Custom criterion', 'description' => 'Something custom.'],
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.settings.speaking-guidelines.reset'))
        ->assertRedirectToRoute('admin.settings.speaking-guidelines.edit')
        ->assertSessionHas('glc_status');

    expect($guidelines->isCustomized())->toBeFalse()
        ->and($guidelines->effective())->toBe($guidelines->defaults());

    $log = AuditLog::query()->where('action', AuditAction::SettingsUpdated)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->details['speaking_guidelines']['action'])->toBe('reset_to_defaults');
});
