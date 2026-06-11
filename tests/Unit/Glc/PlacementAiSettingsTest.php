<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Models\Setting;
use App\Services\Glc\Ai\PlacementAiSettings;

beforeEach(function (): void {
    $this->settings = app(PlacementAiSettings::class);
});

it('returns the configured default selection when nothing is stored', function (): void {
    expect($this->settings->selection(PlacementAiSettings::TASK_WRITING))->toBe([
        'provider' => 'gemini',
        'model' => 'gemini-2.5-flash',
    ]);
});

it('persists and returns an updated selection', function (): void {
    $this->settings->updateSelection(PlacementAiSettings::TASK_WRITING, 'kimi', 'kimi-k2.6');

    expect($this->settings->selection(PlacementAiSettings::TASK_WRITING))->toBe([
        'provider' => 'kimi',
        'model' => 'kimi-k2.6',
    ]);
});

it('falls back to the default when a stored selection is no longer in the catalog', function (): void {
    Setting::set(SettingKey::GlcPlacementAiSelections, json_encode([
        PlacementAiSettings::TASK_SPEAKING => ['provider' => 'openai', 'model' => 'retired-model'],
    ]));

    expect($this->settings->selection(PlacementAiSettings::TASK_SPEAKING))->toBe([
        'provider' => 'gemini',
        'model' => 'gemini-2.5-flash',
    ]);
});

it('stores api keys encrypted and never in plaintext', function (): void {
    $this->settings->updateApiKey('openai', 'sk-secret-value-1234');

    $raw = Setting::get(SettingKey::GlcAiProviderKeys);

    expect($raw)->not->toContain('sk-secret-value-1234')
        ->and($this->settings->apiKey('openai'))->toBe('sk-secret-value-1234');

    $status = $this->settings->apiKeyStatus('openai');

    expect($status['stored'])->toBeTrue()
        ->and($status['masked'])->toEndWith('1234')
        ->and($status['masked'])->not->toContain('sk-secret');
});

it('removes a stored api key when given null', function (): void {
    $this->settings->updateApiKey('openai', 'sk-secret-value-1234');
    $this->settings->updateApiKey('openai', null);

    expect($this->settings->apiKey('openai'))->toBeNull()
        ->and($this->settings->apiKeyStatus('openai')['stored'])->toBeFalse();
});

it('maps aliased providers to a shared credential', function (): void {
    expect($this->settings->credentialFor('groq-stt'))->toBe('groq')
        ->and($this->settings->credentialFor('gemini'))->toBe('gemini');
});

it('hydrates provider config with stored keys', function (): void {
    config(['ai.providers.minimax.key' => null]);

    $this->settings->updateApiKey('minimax', 'mmx-key-9999');
    $this->settings->hydrateProviderConfig();

    expect(config('ai.providers.minimax.key'))->toBe('mmx-key-9999');
});

it('reports whether a task has a usable key', function (): void {
    config(['ai.providers.gemini.key' => null]);

    expect($this->settings->taskIsConfigured(PlacementAiSettings::TASK_WRITING))->toBeFalse();

    $this->settings->updateApiKey('gemini', 'gm-key-1');

    expect($this->settings->taskIsConfigured(PlacementAiSettings::TASK_WRITING))->toBeTrue();
});

it('rejects unknown tasks', function (): void {
    $this->settings->selection('nonsense_task');
})->throws(InvalidArgumentException::class);
