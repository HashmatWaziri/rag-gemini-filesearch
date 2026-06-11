<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\SettingKey;
use App\Models\Glc\AuditLog;
use App\Models\Glc\CurriculumDocument;
use App\Models\Setting;
use App\Models\User;
use App\Services\Glc\Admin\TutorOperationalSettings;

beforeEach(function (): void {
    $this->withoutVite();
});

function defaultTutorOperationalPayload(): array
{
    $defaults = app(TutorOperationalSettings::class)->defaults();

    return [
        'rotation_threshold_pairs' => $defaults['rotation_threshold_pairs'],
        'rotation_summarize_pairs' => $defaults['rotation_summarize_pairs'],
        'violation_notification_threshold' => $defaults['violation_notification_threshold'],
        'violation_notification_window_days' => $defaults['violation_notification_window_days'],
    ];
}

it('redirects guests and blocks non-admin roles', function (): void {
    $this->get(route('admin.settings.edit'))->assertRedirectToRoute('login');

    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)->get(route('admin.settings.edit'))->assertForbidden();
    $this->actingAs($teacher)->put(route('admin.settings.update'))->assertForbidden();
});

it('shows config defaults as effective values when no setting exists', function (): void {
    $admin = User::factory()->admin()->create();

    config()->set('glc.placement.section_time_limits.reading', 900);

    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/admin/settings/edit')
            ->has('sections', 5)
            ->where('defaults.reading', 900)
            ->where('effective.reading', 900)
            ->where('bounds.min', 60)
            ->where('bounds.max', 7200));
});

it('summarizes AI Tutor material health by lifecycle state', function (): void {
    $admin = User::factory()->admin()->create();

    CurriculumDocument::factory()->count(2)->create();
    CurriculumDocument::factory()->published()->create();
    CurriculumDocument::factory()->publishing()->create();
    CurriculumDocument::factory()->publishFailed()->create();
    CurriculumDocument::factory()->archived()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tutorMaterials.counts.draft', 2)
            ->where('tutorMaterials.counts.publishing', 1)
            ->where('tutorMaterials.counts.published', 1)
            ->where('tutorMaterials.counts.publish_failed', 1)
            ->where('tutorMaterials.counts.archived', 1)
            ->has('tutorMaterials.rebuild_available'));
});

it('shows zero counts for AI Tutor materials when none exist', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('tutorMaterials.counts.draft', 0)
            ->where('tutorMaterials.counts.publish_failed', 0));
});

it('overrides config defaults with the stored setting', function (): void {
    $admin = User::factory()->admin()->create();

    Setting::set(SettingKey::GlcSectionTimeLimits, json_encode(['reading' => 1200]));

    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertInertia(fn ($page) => $page
            ->where('effective.reading', 1200)
            ->where('effective.writing', config()->integer('glc.placement.section_time_limits.writing')));
});

it('updates section time limits within bounds and audits the change', function (): void {
    $admin = User::factory()->admin()->create();

    $limits = [
        'reading' => 600,
        'grammar_vocabulary' => 720,
        'listening' => 480,
        'writing' => 1800,
        'speaking' => 300,
    ];

    $tutorOperational = [
        'rotation_threshold_pairs' => 35,
        'rotation_summarize_pairs' => 15,
        'violation_notification_threshold' => 4,
        'violation_notification_window_days' => 10,
    ];

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'section_time_limits' => $limits,
            'tutor_operational' => $tutorOperational,
        ])
        ->assertRedirectToRoute('admin.settings.edit');

    $stored = json_decode((string) Setting::get(SettingKey::GlcSectionTimeLimits), true);

    expect($stored)->toBe($limits);

    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertInertia(fn ($page) => $page->where('effective.reading', 600));

    $log = AuditLog::query()->where('action', AuditAction::SettingsUpdated)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->details['section_time_limits'])->toMatchArray($limits)
        ->and($log->details['tutor_operational'])->toMatchArray($tutorOperational);
});

it('rejects limits outside the 60..7200 second bounds', function (int $reading): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'section_time_limits' => [
                'reading' => $reading,
                'grammar_vocabulary' => 720,
                'listening' => 480,
                'writing' => 1800,
                'speaking' => 300,
            ],
            'tutor_operational' => defaultTutorOperationalPayload(),
        ])
        ->assertSessionHasErrors('section_time_limits.reading');

    expect(Setting::get(SettingKey::GlcSectionTimeLimits))->toBeNull();
})->with([59, 7201, 0, -100]);

it('requires every section limit', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'section_time_limits' => ['reading' => 600],
            'tutor_operational' => defaultTutorOperationalPayload(),
        ])
        ->assertSessionHasErrors([
            'section_time_limits.grammar_vocabulary',
            'section_time_limits.listening',
            'section_time_limits.writing',
            'section_time_limits.speaking',
        ]);
});

it('accepts the boundary values 60 and 7200', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'section_time_limits' => [
                'reading' => 60,
                'grammar_vocabulary' => 7200,
                'listening' => 600,
                'writing' => 1500,
                'speaking' => 480,
            ],
            'tutor_operational' => defaultTutorOperationalPayload(),
        ])
        ->assertSessionHasNoErrors();

    $stored = json_decode((string) Setting::get(SettingKey::GlcSectionTimeLimits), true);

    expect($stored['reading'])->toBe(60)
        ->and($stored['grammar_vocabulary'])->toBe(7200);
});
