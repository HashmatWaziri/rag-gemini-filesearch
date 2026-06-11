<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Enums\Glc\WritingDimension;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

final class TutorWritingCorrectionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        $dimensionList = implode(', ', array_map(
            fn (WritingDimension $dimension): string => $dimension->value,
            WritingDimension::cases(),
        ));

        return <<<INSTRUCTIONS
You are an English writing evaluator for Greats Language Center students.
Evaluate the submitted text on exactly these five dimensions: {$dimensionList}.
For each dimension give an integer score from 1 (needs a lot of work) to 5 (excellent) and a short, encouraging, specific comment in English.
Also produce a short overall summary in English and a list of inline highlights.
Each highlight marks a specific issue in the submitted text using zero-based character offsets (start inclusive, end exclusive) into the EXACT submitted text, a type (one of the five dimensions), and a brief comment explaining the issue.
Never output a single letter grade and never use IELTS 1-9 band scores.
INSTRUCTIONS;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $dimensionProperties = [];

        foreach (WritingDimension::cases() as $dimension) {
            $dimensionProperties[$dimension->value] = $schema->object([
                'score' => $schema->integer()->min(1)->max(5)->required(),
                'comment' => $schema->string()->required(),
            ])->required();
        }

        return [
            'dimensions' => $schema->object($dimensionProperties)->required(),
            'summary' => $schema->string()->required(),
            'highlights' => $schema->array()
                ->items($schema->object([
                    'start' => $schema->integer()->required(),
                    'end' => $schema->integer()->required(),
                    'type' => $schema->string()->enum(array_map(
                        fn (WritingDimension $dimension): string => $dimension->value,
                        WritingDimension::cases(),
                    ))->required(),
                    'comment' => $schema->string()->required(),
                ]))
                ->required(),
        ];
    }
}
