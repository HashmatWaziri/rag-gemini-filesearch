<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\SettingKey;
use App\Models\Glc\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Glc\Ai\PlacementAiSettings;

beforeEach(function (): void {
    $this->withoutVite();
});

it('redirects guests and blocks non-admin roles', function (): void {
    $this->get(route('admin.settings.ai.edit'))->assertRedirectToRoute('login');

    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)->get(route('admin.settings.ai.edit'))->assertForbidden();
    $this->actingAs($teacher)->put(route('admin.settings.ai.selection.update'))->assertForbidden();
    $this->actingAs($teacher)->put(route('admin.settings.ai.keys.update'))->assertForbidden();

    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('admin.settings.ai.edit'))->assertForbidden();
});

it('renders the catalog, selections, and key status for an admin', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings.ai.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/admin/settings/ai')
            ->where('tasks.writing_evaluation.providers.gemini.models', fn ($models) => collect($models)->toArray()['gemini-2.5-flash']['input_per_mtok'] === 0.3)
            ->where('tasks.writing_evaluation.selection.provider', 'gemini')
            ->where('tasks.writing_evaluation.selection.model', 'gemini-2.5-flash')
            ->has('tasks.speaking_transcription.providers.groq-stt')
            ->where('tasks.speaking_transcription.providers.groq-stt.credential', 'groq')
            ->has('tasks.speaking_transcription.selection')
            ->has('tasks.tutor_chat')
            ->has('tasks.tutor_writing')
            ->has('tasks.tutor_progress')
            ->where('pricing_retrieved_at', config('glc-ai.pricing_retrieved_at'))
        );
});

it('never serializes a stored raw API key to the page', function (): void {
    $admin = User::factory()->admin()->create();

    app(PlacementAiSettings::class)->updateApiKey('gemini', 'sk-super-secret-value-1234');

    $response = $this->actingAs($admin)->get(route('admin.settings.ai.edit'));

    $response->assertOk()
        ->assertDontSee('sk-super-secret-value-1234')
        ->assertInertia(fn ($page) => $page
            ->where('credentials', fn ($credentials) => collect($credentials)
                ->firstWhere('credential', 'gemini')['status'] === [
                    'stored' => true,
                    'masked' => str_repeat('•', 8).'1234',
                    'env_fallback' => false,
                ]));
});

it('persists a model selection and writes an audit log row', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.ai.selection.update'), [
            'task' => 'writing_evaluation',
            'provider' => 'deepseek',
            'model' => 'deepseek-v4-flash',
        ])
        ->assertRedirectToRoute('admin.settings.ai.edit')
        ->assertSessionHas('glc_status');

    expect(app(PlacementAiSettings::class)->selection('writing_evaluation'))->toBe([
        'provider' => 'deepseek',
        'model' => 'deepseek-v4-flash',
    ]);

    $log = AuditLog::query()->where('action', AuditAction::SettingsUpdated)->firstOrFail();

    expect($log->actor_id)->toBe($admin->id)
        ->and($log->details['ai_selection'])->toBe([
            'task' => 'writing_evaluation',
            'provider' => 'deepseek',
            'model' => 'deepseek-v4-flash',
        ]);
});

it('rejects invalid tasks, providers, and models', function (array $payload, string $errorKey): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.ai.selection.update'), $payload)
        ->assertSessionHasErrors($errorKey);

    expect(Setting::get(SettingKey::GlcPlacementAiSelections))->toBeNull();
})->with([
    'unknown task' => [
        ['task' => 'image_generation', 'provider' => 'gemini', 'model' => 'gemini-2.5-flash'],
        'task',
    ],
    'provider not in task catalog' => [
        ['task' => 'writing_evaluation', 'provider' => 'eleven', 'model' => 'scribe_v1'],
        'provider',
    ],
    'unknown provider' => [
        ['task' => 'speaking_transcription', 'provider' => 'nonexistent', 'model' => 'whisper-1'],
        'provider',
    ],
    'model not under provider' => [
        ['task' => 'writing_evaluation', 'provider' => 'gemini', 'model' => 'gpt-5.5'],
        'model',
    ],
]);

it('stores an API key encrypted, never in plaintext', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.ai.keys.update'), [
            'credential' => 'openai',
            'api_key' => 'sk-plaintext-test-key-abcd',
        ])
        ->assertRedirectToRoute('admin.settings.ai.edit')
        ->assertSessionHas('glc_status');

    $raw = (string) Setting::get(SettingKey::GlcAiProviderKeys);

    expect($raw)->not->toContain('sk-plaintext-test-key-abcd');

    $settings = app(PlacementAiSettings::class);

    expect($settings->apiKey('openai'))->toBe('sk-plaintext-test-key-abcd')
        ->and($settings->apiKeyStatus('openai'))->toMatchArray([
            'stored' => true,
            'masked' => str_repeat('•', 8).'abcd',
        ]);
});

it('removes a stored API key when api_key is null', function (): void {
    $admin = User::factory()->admin()->create();

    app(PlacementAiSettings::class)->updateApiKey('groq', 'gsk-old-key-9999');

    $this->actingAs($admin)
        ->put(route('admin.settings.ai.keys.update'), [
            'credential' => 'groq',
            'api_key' => null,
        ])
        ->assertRedirectToRoute('admin.settings.ai.edit');

    $settings = app(PlacementAiSettings::class);

    expect($settings->apiKey('groq'))->toBeNull()
        ->and($settings->apiKeyStatus('groq')['stored'])->toBeFalse();

    $log = AuditLog::query()->where('action', AuditAction::SettingsUpdated)->firstOrFail();

    expect($log->details['ai_provider_key'])->toBe([
        'credential' => 'groq',
        'action' => 'removed',
    ]);
});

it('rejects unknown credentials', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.ai.keys.update'), [
            'credential' => 'groq-stt',
            'api_key' => 'gsk-whatever',
        ])
        ->assertSessionHasErrors('credential');

    expect(Setting::get(SettingKey::GlcAiProviderKeys))->toBeNull();
});

it('never records the API key in the audit log', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.ai.keys.update'), [
            'credential' => 'anthropic',
            'api_key' => 'sk-ant-very-secret-key',
        ]);

    $log = AuditLog::query()->where('action', AuditAction::SettingsUpdated)->firstOrFail();

    expect(json_encode($log->details))->not->toContain('sk-ant-very-secret-key')
        ->and($log->details['ai_provider_key'])->toBe([
            'credential' => 'anthropic',
            'action' => 'set',
        ]);
});
