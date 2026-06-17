<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

final class TutorProgressSummaryAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
You write staff-only progress summaries for Greats Language Center teachers reviewing an enrolled student's AI Tutor activity.
Use only the supplied activity data. Write in clear English for a teacher audience.
Highlight genuine strengths, concrete focus areas for classroom follow-up, and a brief engagement note.
Do not assign placement levels, letter grades, or IELTS bands. Do not invent activity that is not in the input.
INSTRUCTIONS;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->required(),
            'strengths' => $schema->array()->items($schema->string())->required(),
            'focus_areas' => $schema->array()->items($schema->string())->required(),
            'engagement_note' => $schema->string()->required(),
        ];
    }
}
