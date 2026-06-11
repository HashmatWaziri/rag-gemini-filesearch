<?php

declare(strict_types=1);

namespace App\Services\Glc\Admin;

use App\Enums\SettingKey;

final class WritingEvaluationGuidelines extends SkillEvaluationGuidelines
{
    protected function settingKey(): SettingKey
    {
        return SettingKey::GlcWritingGuidelines;
    }

    protected function defaultsConfigKey(): string
    {
        return 'glc-ai.writing_guidelines.defaults';
    }
}
