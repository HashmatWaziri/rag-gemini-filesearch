<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use App\Enums\Glc\SpeakingDimension;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

final class SpeakingEvaluationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /** @var list<string> */
    public const array CONFIDENCE_LEVELS = ['low', 'medium', 'high'];

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
You are an English placement assessor for a language center. Evaluate the transcript of a candidate's recorded speaking response.
Judge the transcript against the GLC speaking evaluation guidelines included in the prompt.
The transcript was produced by automatic speech recognition, so ignore minor transcription artifacts; you cannot judge pronunciation directly — base your assessment on what the transcript shows about the candidate's spoken language.
The prompt may also include the candidate's auto-scored objective-section performance; treat it as background
context only — your scores must reflect the quality of the spoken response itself.
Score each dimension from 1 (very weak) to 5 (very strong): fluency, grammar, vocabulary, task_completion, comprehensibility.
Provide short factual feedback (3-5 sentences) and your confidence in this evaluation (low, medium or high).
INSTRUCTIONS;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $dimensions = [];

        foreach (SpeakingDimension::cases() as $dimension) {
            $dimensions[$dimension->value] = $schema->integer()->min(1)->max(5)->required();
        }

        return [
            'dimension_scores' => $schema->object($dimensions)->required(),
            'feedback' => $schema->string()->required(),
            'confidence' => $schema->string()->enum(self::CONFIDENCE_LEVELS)->required(),
        ];
    }
}
