<?php

declare(strict_types=1);

namespace App\Enums;

enum SettingKey: string
{
    case GeminiFileSearchStoreName = 'gemini_file_search_store_name';
    case GlcCurriculumStoreName = 'glc_curriculum_store_name';
    case GlcSectionTimeLimits = 'glc_section_time_limits';
}
