<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Enums\Glc\WritingDimension;
use App\Models\Glc\WritingSubmission;

final class WritingCorrectionService
{
    public const FAILURE_MESSAGE = 'We could not evaluate your writing right now. Please try submitting it again later.';

    public function __construct(private readonly GeminiTutorClient $client) {}

    public function evaluate(WritingSubmission $submission): void
    {
        $response = $this->client->generateContent($this->buildPayload($submission));
        $text = $response !== null ? $this->client->extractText($response) : null;
        $decoded = is_string($text) ? json_decode($text, true) : null;
        $dimensions = is_array($decoded) ? $this->normalizeDimensions($decoded) : null;

        if (! is_array($decoded) || $dimensions === null) {
            $submission->update([
                'status' => 'failed',
                'error' => self::FAILURE_MESSAGE,
            ]);

            return;
        }

        $summary = data_get($decoded, 'summary');

        $submission->update([
            'feedback' => [
                'dimensions' => $dimensions,
                'summary' => is_string($summary) ? $summary : '',
            ],
            'highlights' => $this->normalizeHighlights($decoded, mb_strlen($submission->text)),
            'status' => 'completed',
            'error' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(WritingSubmission $submission): array
    {
        $dimensionList = implode(', ', array_map(
            fn (WritingDimension $dimension): string => $dimension->value,
            WritingDimension::cases(),
        ));

        $dimensionSchema = [];

        foreach (WritingDimension::cases() as $dimension) {
            $dimensionSchema[$dimension->value] = [
                'type' => 'OBJECT',
                'properties' => [
                    'score' => ['type' => 'INTEGER'],
                    'comment' => ['type' => 'STRING'],
                ],
                'required' => ['score', 'comment'],
            ];
        }

        $systemText = <<<PROMPT
You are an English writing evaluator for Greats Language Center students.
Evaluate the submitted text on exactly these five dimensions: {$dimensionList}.
For each dimension give an integer score from 1 (needs a lot of work) to 5 (excellent) and a short, encouraging, specific comment in English.
Also produce a short overall summary in English and a list of inline highlights.
Each highlight marks a specific issue in the submitted text using zero-based character offsets (start inclusive, end exclusive) into the EXACT submitted text, a type (one of the five dimensions), and a brief comment explaining the issue.
Never output a single letter grade and never use IELTS 1-9 band scores.
Respond with JSON only.
PROMPT;

        return [
            'system_instruction' => ['parts' => [['text' => $systemText]]],
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => "Evaluate this student writing:\n\n".$submission->text]],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'dimensions' => [
                            'type' => 'OBJECT',
                            'properties' => $dimensionSchema,
                            'required' => array_keys($dimensionSchema),
                        ],
                        'summary' => ['type' => 'STRING'],
                        'highlights' => [
                            'type' => 'ARRAY',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'start' => ['type' => 'INTEGER'],
                                    'end' => ['type' => 'INTEGER'],
                                    'type' => ['type' => 'STRING', 'enum' => array_keys($dimensionSchema)],
                                    'comment' => ['type' => 'STRING'],
                                ],
                                'required' => ['start', 'end', 'type', 'comment'],
                            ],
                        ],
                    ],
                    'required' => ['dimensions', 'summary'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array<string, array{score: int, comment: string}>|null
     */
    private function normalizeDimensions(array $decoded): ?array
    {
        $normalized = [];

        foreach (WritingDimension::cases() as $dimension) {
            $score = data_get($decoded, 'dimensions.'.$dimension->value.'.score');
            $comment = data_get($decoded, 'dimensions.'.$dimension->value.'.comment');

            if (! is_numeric($score)) {
                return null;
            }

            $normalized[$dimension->value] = [
                'score' => max(1, min(5, (int) $score)),
                'comment' => is_string($comment) ? $comment : '',
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return list<array{start: int, end: int, type: string, comment: string}>
     */
    private function normalizeHighlights(array $decoded, int $textLength): array
    {
        $raw = data_get($decoded, 'highlights');

        if (! is_array($raw)) {
            return [];
        }

        $types = array_map(
            fn (WritingDimension $dimension): string => $dimension->value,
            WritingDimension::cases(),
        );

        $valid = collect($raw)
            ->filter(function (mixed $highlight) use ($types, $textLength): bool {
                if (! is_array($highlight)) {
                    return false;
                }

                $start = $highlight['start'] ?? null;
                $end = $highlight['end'] ?? null;
                $type = $highlight['type'] ?? null;

                return is_int($start) && is_int($end)
                    && $start >= 0 && $end > $start && $end <= $textLength
                    && is_string($type) && in_array($type, $types, true);
            })
            ->map(fn (array $highlight): array => [
                'start' => $highlight['start'],
                'end' => $highlight['end'],
                'type' => $highlight['type'],
                'comment' => is_string($highlight['comment'] ?? null) ? $highlight['comment'] : '',
            ])
            ->sortBy('start')
            ->values();

        $result = [];
        $cursor = 0;

        foreach ($valid as $highlight) {
            if ($highlight['start'] < $cursor) {
                continue;
            }

            $result[] = $highlight;
            $cursor = $highlight['end'];
        }

        return $result;
    }
}
