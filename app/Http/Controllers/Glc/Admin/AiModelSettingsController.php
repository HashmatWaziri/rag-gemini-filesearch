<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Services\Glc\Ai\PlacementAiSettings;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AiModelSettingsController
{
    private const array TASK_LABELS = [
        PlacementAiSettings::TASK_WRITING => 'Writing evaluation',
        PlacementAiSettings::TASK_SPEAKING_EVALUATION => 'Speaking evaluation',
        PlacementAiSettings::TASK_SPEAKING => 'Speaking transcription',
        PlacementAiSettings::TASK_TUTOR_CHAT => 'AI Tutor chat',
        PlacementAiSettings::TASK_TUTOR_WRITING => 'AI Tutor writing correction',
        PlacementAiSettings::TASK_TUTOR_PROGRESS => 'AI Tutor progress reports',
    ];

    public function __construct(
        private PlacementAiSettings $settings,
        private AuditLogger $auditLogger,
    ) {}

    public function edit(Request $request): Response
    {
        $tasks = [];

        foreach (PlacementAiSettings::TASKS as $task) {
            $tasks[$task] = [
                'label' => self::TASK_LABELS[$task],
                'providers' => $this->providersFor($task),
                'selection' => $this->settings->selection($task),
                'configured' => $this->settings->taskIsConfigured($task),
            ];
        }

        return Inertia::render('glc/admin/settings/ai', [
            'tasks' => $tasks,
            'credentials' => $this->credentials(),
            'pricing_retrieved_at' => config()->string('glc-ai.pricing_retrieved_at'),
            'status' => $request->session()->get('glc_status'),
        ]);
    }

    public function updateSelection(Request $request): RedirectResponse
    {
        $task = $request->string('task')->toString();
        $catalog = in_array($task, PlacementAiSettings::TASKS, true)
            ? $this->settings->catalog($task)
            : [];
        $provider = $request->string('provider')->toString();

        $validated = $request->validate([
            'task' => ['required', 'string', Rule::in(PlacementAiSettings::TASKS)],
            'provider' => ['required', 'string', Rule::in(array_keys($catalog))],
            'model' => ['required', 'string', Rule::in(array_keys($catalog[$provider]['models'] ?? []))],
        ]);

        $this->settings->updateSelection($validated['task'], $validated['provider'], $validated['model']);

        $this->auditLogger->log(AuditAction::SettingsUpdated, $request->user(), null, [
            'ai_selection' => [
                'task' => $validated['task'],
                'provider' => $validated['provider'],
                'model' => $validated['model'],
            ],
        ]);

        return to_route('admin.settings.ai.edit')->with('glc_status', 'AI model selection saved.');
    }

    public function updateKey(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'credential' => ['required', 'string', Rule::in($this->credentialNames())],
            'api_key' => ['nullable', 'string', 'max:2000'],
        ]);

        $apiKey = $validated['api_key'] ?? null;
        $removed = $apiKey === null || mb_trim($apiKey) === '';

        $this->settings->updateApiKey($validated['credential'], $removed ? null : $apiKey);

        $this->auditLogger->log(AuditAction::SettingsUpdated, $request->user(), null, [
            'ai_provider_key' => [
                'credential' => $validated['credential'],
                'action' => $removed ? 'removed' : 'set',
            ],
        ]);

        return to_route('admin.settings.ai.edit')->with(
            'glc_status',
            $removed ? 'API key removed.' : 'API key saved.',
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function providersFor(string $task): array
    {
        $providers = [];

        foreach ($this->settings->catalog($task) as $name => $provider) {
            $providers[$name] = [
                ...$provider,
                'credential' => $this->settings->credentialFor($name),
            ];
        }

        return $providers;
    }

    /**
     * @return list<array{credential: string, provider_labels: list<string>, status: array{stored: bool, masked: string|null, env_fallback: bool}}>
     */
    private function credentials(): array
    {
        $labelsByCredential = [];

        foreach (PlacementAiSettings::TASKS as $task) {
            foreach ($this->settings->catalog($task) as $name => $provider) {
                $credential = $this->settings->credentialFor($name);
                $label = is_string($provider['label'] ?? null) ? $provider['label'] : $name;
                $labelsByCredential[$credential][$label] = true;
            }
        }

        $credentials = [];

        foreach ($labelsByCredential as $credential => $labels) {
            $credentials[] = [
                'credential' => $credential,
                'provider_labels' => array_keys($labels),
                'status' => $this->settings->apiKeyStatus($credential),
            ];
        }

        return $credentials;
    }

    /**
     * @return list<string>
     */
    private function credentialNames(): array
    {
        $names = [];

        foreach (PlacementAiSettings::TASKS as $task) {
            foreach (array_keys($this->settings->catalog($task)) as $provider) {
                $names[] = $this->settings->credentialFor($provider);
            }
        }

        return array_values(array_unique($names));
    }
}
