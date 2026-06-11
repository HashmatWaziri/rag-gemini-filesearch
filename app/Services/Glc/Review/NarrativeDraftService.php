<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use App\Enums\Glc\PlacementAiDraftStatus;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementReview;
use RuntimeException;

final readonly class NarrativeDraftService
{
    public function __construct(private GeminiClient $gemini) {}

    /**
     * @return array{strengths: string, areas_to_improve: string, recommendation: string, next_steps: string}
     */
    public function draft(PlacementReview $review): array
    {
        $result = $this->gemini->generateJson(
            [['text' => $this->prompt($review)]],
            [
                'type' => 'OBJECT',
                'properties' => [
                    'strengths' => ['type' => 'STRING'],
                    'areas_to_improve' => ['type' => 'STRING'],
                    'recommendation' => ['type' => 'STRING'],
                    'next_steps' => ['type' => 'STRING'],
                ],
                'required' => ['strengths', 'areas_to_improve', 'recommendation', 'next_steps'],
            ],
        );

        $fields = [];

        foreach (['strengths', 'areas_to_improve', 'recommendation', 'next_steps'] as $key) {
            $value = $result[$key] ?? null;

            if (! is_string($value) || mb_trim($value) === '') {
                throw new RuntimeException(sprintf('Gemini draft is missing the %s field.', $key));
            }

            $fields[$key] = mb_trim($value);
        }

        /** @var array{strengths: string, areas_to_improve: string, recommendation: string, next_steps: string} $fields */
        return $fields;
    }

    private function prompt(PlacementReview $review): string
    {
        $attempt = $review->attempt;
        $score = $attempt->score;

        $lines = [
            'You are helping a language-center reviewer draft a placement result narrative.',
            'Write four short fields in plain English addressed to the candidate/parents:',
            'strengths, areas_to_improve, recommendation, next_steps (2-3 sentences each).',
            'Do not mention AI, scores as raw percentages, or internal review flags. Respond with JSON only.',
            '',
            'Assessment context:',
        ];

        if ($score !== null) {
            foreach (PlacementSection::ordered() as $section) {
                $value = $score->section_scores[$section->value] ?? null;
                $lines[] = sprintf('- %s: %s', $section->label(), $value === null ? 'not yet evaluated' : $value.'%');
            }

            $lines[] = sprintf('- Suggested overall level: %s', $score->suggested_level?->label() ?? 'unknown');
        }

        if ($review->final_level !== null) {
            $lines[] = sprintf('- Confirmed final level: %s', $review->final_level->label());
        }

        foreach ($attempt->aiDrafts as $draft) {
            if ($draft->status === PlacementAiDraftStatus::Completed && $draft->feedback !== null) {
                $lines[] = sprintf('- %s evaluation notes: %s', $draft->section->label(), $draft->feedback);
            }
        }

        return implode("\n", $lines);
    }
}
