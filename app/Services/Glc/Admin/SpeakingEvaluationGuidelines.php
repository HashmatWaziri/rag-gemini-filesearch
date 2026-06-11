<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\SettingKey;

final class SpeakingEvaluationGuidelines extends SkillEvaluationGuidelines
{
    protected function settingKey(): SettingKey
    {
        return SettingKey::GlcSpeakingGuidelines;
    }

    protected function defaultsConfigKey(): string
    {
        return 'glc-ai.speaking_guidelines.defaults';
    }
}
