<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementSection;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

final class PlacementRecommendationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /** @var list<string> */
    public const array CONFIDENCE_LEVELS = ['low', 'medium', 'high'];

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
You are an English placement assessor for a language center producing a staff-only provisional recommendation.
You will receive the candidate's full assessment evidence: auto-scored objective sections (Reading, Grammar & Vocabulary, Listening),
AI evaluation results for Writing and Speaking, and the GLC evaluation guidelines for both productive skills.
Recommend exactly one overall GLC level and one GLC level per skill, chosen from the seven GLC levels provided in the prompt.
Weigh all five skills equally and base each skill level on the evidence for that skill; do not inflate weak skills because of strong ones.
For each skill write a short factual summary (2-3 sentences) describing what the candidate demonstrated, written for GLC staff reviewers.
Also provide a brief overall rationale (2-4 sentences) and your confidence (low, medium or high).
If evidence for a skill is missing or failed, choose the closest defensible level from the remaining evidence and say so in that skill's summary.
INSTRUCTIONS;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $levels = array_map(fn (GlcLevel $level): string => $level->value, GlcLevel::cases());

        $skillLevels = [];
        $skillSummaries = [];

        foreach (PlacementSection::ordered() as $section) {
            $skillLevels[$section->value] = $schema->string()->enum($levels)->required();
            $skillSummaries[$section->value] = $schema->string()->required();
        }

        return [
            'recommended_level' => $schema->string()->enum($levels)->required(),
            'skill_levels' => $schema->object($skillLevels)->required(),
            'skill_summaries' => $schema->object($skillSummaries)->required(),
            'confidence' => $schema->string()->enum(self::CONFIDENCE_LEVELS)->required(),
            'rationale' => $schema->string()->required(),
        ];
    }
}
