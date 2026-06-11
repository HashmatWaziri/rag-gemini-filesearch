<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use App\Enums\Glc\WritingDimension;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

final class WritingEvaluationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /** @var list<string> */
    public const array CONFIDENCE_LEVELS = ['low', 'medium', 'high'];

    public function instructions(): string
    {
        return <<<'INSTRUCTIONS'
You are an English placement assessor for a language center. Evaluate the candidate response you are given.
Judge the response against the GLC writing evaluation guidelines included in the prompt.
The prompt may also include the candidate's auto-scored objective-section performance; treat it as background
context only — your scores must reflect the quality of the essay itself.
Score each dimension from 1 (very weak) to 5 (very strong): grammar, vocabulary, structure, coherence, task_completion.
Provide short factual feedback (3-5 sentences) and your confidence in this evaluation (low, medium or high).
INSTRUCTIONS;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $dimensions = [];

        foreach (WritingDimension::cases() as $dimension) {
            $dimensions[$dimension->value] = $schema->integer()->min(1)->max(5)->required();
        }

        return [
            'dimension_scores' => $schema->object($dimensions)->required(),
            'feedback' => $schema->string()->required(),
            'confidence' => $schema->string()->enum(self::CONFIDENCE_LEVELS)->required(),
        ];
    }
}
