<?php

declare(strict_types=1);

namespace App\Enums;

enum SettingKey: string
{
    case GeminiFileSearchStoreName = 'gemini_file_search_store_name';
    case GlcCurriculumStoreName = 'glc_curriculum_store_name';
    case GlcSectionTimeLimits = 'glc_section_time_limits';
    case GlcPlacementAiSelections = 'glc_placement_ai_selections';
    case GlcAiProviderKeys = 'glc_ai_provider_keys';
    case GlcWritingGuidelines = 'glc_writing_guidelines';
    case GlcSpeakingGuidelines = 'glc_speaking_guidelines';
    case GlcTutorOperationalSettings = 'glc_tutor_operational_settings';
    case GlcPlacementScoringSettings = 'glc_placement_scoring_settings';
    case GlcAiCostSettings = 'glc_ai_cost_settings';
}
