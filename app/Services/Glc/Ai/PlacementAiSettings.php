<?php

declare(strict_types=1);

namespace App\Services\Glc\Ai;

use App\Enums\SettingKey;
use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;

final class PlacementAiSettings
{
    public const string TASK_WRITING = 'writing_evaluation';

    public const string TASK_SPEAKING_EVALUATION = 'speaking_evaluation';

    public const string TASK_SPEAKING = 'speaking_transcription';

    public const string TASK_TUTOR_CHAT = 'tutor_chat';

    public const string TASK_TUTOR_WRITING = 'tutor_writing';

    public const string TASK_TUTOR_PROGRESS = 'tutor_progress';

    /** @var list<string> */
    public const array TASKS = [
        self::TASK_WRITING,
        self::TASK_SPEAKING_EVALUATION,
        self::TASK_SPEAKING,
        self::TASK_TUTOR_CHAT,
        self::TASK_TUTOR_WRITING,
        self::TASK_TUTOR_PROGRESS,
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public function catalog(string $task): array
    {
        $this->assertTask($task);

        /** @var array<string, array<string, mixed>> $providers */
        $providers = config('glc-ai.'.$task.'.providers', []);

        return $providers;
    }

    /**
     * @return array{provider: string, model: string}
     */
    public function selection(string $task): array
    {
        $this->assertTask($task);

        $default = [
            'provider' => config()->string('glc-ai.'.$task.'.default.provider'),
            'model' => config()->string('glc-ai.'.$task.'.default.model'),
        ];

        $stored = $this->decodeJsonSetting(SettingKey::GlcPlacementAiSelections);
        $selection = $stored[$task] ?? null;

        if (! is_array($selection) || ! is_string($selection['provider'] ?? null) || ! is_string($selection['model'] ?? null)) {
            return $default;
        }

        $catalog = $this->catalog($task);
        $provider = $selection['provider'];
        $model = $selection['model'];

        if (! isset($catalog[$provider]['models'][$model])) {
            return $default;
        }

        return ['provider' => $provider, 'model' => $model];
    }

    public function updateSelection(string $task, string $provider, string $model): void
    {
        $this->assertTask($task);

        $stored = $this->decodeJsonSetting(SettingKey::GlcPlacementAiSelections);
        $stored[$task] = ['provider' => $provider, 'model' => $model];

        Setting::set(SettingKey::GlcPlacementAiSelections, json_encode($stored));
    }

    public function updateApiKey(string $credential, ?string $apiKey): void
    {
        $keys = $this->decodeJsonSetting(SettingKey::GlcAiProviderKeys);

        if ($apiKey === null || mb_trim($apiKey) === '') {
            unset($keys[$credential]);
        } else {
            $keys[$credential] = Crypt::encryptString(mb_trim($apiKey));
        }

        Setting::set(SettingKey::GlcAiProviderKeys, json_encode($keys));
    }

    public function apiKey(string $credential): ?string
    {
        $keys = $this->decodeJsonSetting(SettingKey::GlcAiProviderKeys);
        $encrypted = $keys[$credential] ?? null;

        return is_string($encrypted) ? Crypt::decryptString($encrypted) : null;
    }

    /**
     * @return array{stored: bool, masked: string|null, env_fallback: bool}
     */
    public function apiKeyStatus(string $credential): array
    {
        $stored = $this->apiKey($credential);
        $envKey = config('ai.providers.'.$credential.'.key');

        return [
            'stored' => $stored !== null,
            'masked' => $stored !== null ? $this->mask($stored) : null,
            'env_fallback' => is_string($envKey) && $envKey !== '',
        ];
    }

    public function credentialFor(string $provider): string
    {
        $aliases = config('glc-ai.credential_aliases', []);
        $alias = is_array($aliases) ? ($aliases[$provider] ?? null) : null;

        return is_string($alias) ? $alias : $provider;
    }

    public function taskIsConfigured(string $task): bool
    {
        $credential = $this->credentialFor($this->selection($task)['provider']);
        $status = $this->apiKeyStatus($credential);

        return $status['stored'] || $status['env_fallback'];
    }

    public function hydrateProviderConfig(): void
    {
        $keys = $this->decodeJsonSetting(SettingKey::GlcAiProviderKeys);

        foreach (array_keys($keys) as $credential) {
            if (config()->has('ai.providers.'.$credential)) {
                $apiKey = $this->apiKey($credential);

                if ($apiKey !== null) {
                    config(['ai.providers.'.$credential.'.key' => $apiKey]);
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonSetting(SettingKey $key): array
    {
        $raw = Setting::get($key);

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function mask(string $secret): string
    {
        $visible = mb_substr($secret, -4);

        return str_repeat('•', 8).$visible;
    }

    private function assertTask(string $task): void
    {
        if (! in_array($task, self::TASKS, true)) {
            throw new InvalidArgumentException(sprintf('Unknown GLC AI task [%s].', $task));
        }
    }
}
