<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\SettingKey;
use App\Models\Setting;

abstract class SkillEvaluationGuidelines
{
    public const int MAX_CRITERIA = 20;

    public const int MAX_TITLE_LENGTH = 120;

    public const int MAX_DESCRIPTION_LENGTH = 1000;

    abstract protected function settingKey(): SettingKey;

    abstract protected function defaultsConfigKey(): string;

    /**
     * @return list<array{title: string, description: string}>
     */
    public function defaults(): array
    {
        /** @var list<array{title: string, description: string}> $defaults */
        $defaults = config($this->defaultsConfigKey(), []);

        return $defaults;
    }

    /**
     * @return list<array{title: string, description: string}>
     */
    public function effective(): array
    {
        $raw = Setting::get($this->settingKey());

        if (! is_string($raw) || $raw === '') {
            return $this->defaults();
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return $this->defaults();
        }

        $criteria = [];

        foreach ($decoded as $criterion) {
            if (is_array($criterion) && is_string($criterion['title'] ?? null) && is_string($criterion['description'] ?? null)) {
                $criteria[] = ['title' => $criterion['title'], 'description' => $criterion['description']];
            }
        }

        return $criteria === [] ? $this->defaults() : $criteria;
    }

    public function isCustomized(): bool
    {
        $raw = Setting::get($this->settingKey());

        return is_string($raw) && $raw !== '';
    }

    /**
     * @param  list<array{title: string, description: string}>  $criteria
     */
    public function update(array $criteria): void
    {
        Setting::set($this->settingKey(), json_encode(array_values($criteria)));
    }

    public function reset(): void
    {
        Setting::query()->where('key', $this->settingKey()->value)->delete();
    }

    public function asPromptBlock(): string
    {
        $lines = [];

        foreach ($this->effective() as $index => $criterion) {
            $lines[] = sprintf('%d. %s: %s', $index + 1, $criterion['title'], $criterion['description']);
        }

        return implode("\n", $lines);
    }
}
