<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use App\Enums\Glc\PlacementAiDraftStatus;
use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\WritingDimension;
use App\Models\Glc\PlacementAiDraft;
use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementAttempt;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final readonly class AiDraftService
{
    private const array CONFIDENCE_LEVELS = ['low', 'medium', 'high'];

    public function __construct(private GeminiClient $gemini) {}

    public function generate(PlacementAttempt $attempt, PlacementSection $section): PlacementAiDraft
    {
        try {
            $payload = match ($section) {
                PlacementSection::Writing => $this->evaluateWriting($attempt),
                PlacementSection::Speaking => $this->evaluateSpeaking($attempt),
                default => throw new RuntimeException(sprintf('AI drafts are not produced for the %s section.', $section->value)),
            };

            return $this->persist($attempt, $section, [
                ...$payload,
                'status' => PlacementAiDraftStatus::Completed,
                'error' => null,
                'generated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            return $this->persist($attempt, $section, [
                'dimension_scores' => null,
                'transcript' => null,
                'feedback' => null,
                'confidence' => null,
                'status' => PlacementAiDraftStatus::Failed,
                'error' => mb_substr($exception->getMessage(), 0, 1000),
                'generated_at' => null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluateWriting(PlacementAttempt $attempt): array
    {
        $answer = $this->sectionAnswer($attempt, PlacementSection::Writing);
        $essay = $answer?->response['text'] ?? null;

        if (! is_string($essay) || mb_trim($essay) === '') {
            throw new RuntimeException('No essay text was found for this attempt.');
        }

        $prompt = <<<'PROMPT'
You are an English placement assessor for a language center. Evaluate the candidate essay below.
Score each dimension from 1 (very weak) to 5 (very strong): grammar, vocabulary, structure, coherence, task_completion.
Provide short factual feedback (3-5 sentences) and your confidence in this evaluation (low, medium or high).
Respond with JSON only.

Candidate essay:
PROMPT;

        $result = $this->gemini->generateJson(
            [['text' => $prompt."\n".$essay]],
            $this->evaluationSchema(withTranscript: false),
        );

        return [
            'dimension_scores' => $this->normalizeDimensions($result),
            'transcript' => null,
            'feedback' => $this->stringField($result, 'feedback'),
            'confidence' => $this->normalizeConfidence($result),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluateSpeaking(PlacementAttempt $attempt): array
    {
        $answer = $this->sectionAnswer($attempt, PlacementSection::Speaking);
        $audioPath = $answer?->response['audio_path'] ?? null;

        if (! is_string($audioPath) || ! Storage::disk('local')->exists($audioPath)) {
            throw new RuntimeException('No speaking recording was found for this attempt.');
        }

        $mimeType = $answer->response['mime_type'] ?? 'audio/webm';

        $prompt = <<<'PROMPT'
You are an English placement assessor for a language center. Listen to the candidate speaking response attached as audio.
First transcribe it, then score each dimension from 1 (very weak) to 5 (very strong): grammar, vocabulary, structure, coherence, task_completion.
Provide short factual feedback (3-5 sentences) and your confidence in this evaluation (low, medium or high).
Respond with JSON only.
PROMPT;

        $result = $this->gemini->generateJson(
            [
                ['text' => $prompt],
                ['inlineData' => [
                    'mimeType' => is_string($mimeType) ? $mimeType : 'audio/webm',
                    'data' => base64_encode((string) Storage::disk('local')->get($audioPath)),
                ]],
            ],
            $this->evaluationSchema(withTranscript: true),
        );

        return [
            'dimension_scores' => $this->normalizeDimensions($result),
            'transcript' => $this->stringField($result, 'transcript'),
            'feedback' => $this->stringField($result, 'feedback'),
            'confidence' => $this->normalizeConfidence($result),
        ];
    }

    private function sectionAnswer(PlacementAttempt $attempt, PlacementSection $section): ?PlacementAnswer
    {
        return $attempt->answers()
            ->whereHas('item', function ($query) use ($section): void {
                $query->where('section', $section)->where('type', PlacementItemType::Prompt);
            })
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluationSchema(bool $withTranscript): array
    {
        $dimensionProperties = [];

        foreach (WritingDimension::cases() as $dimension) {
            $dimensionProperties[$dimension->value] = ['type' => 'INTEGER'];
        }

        $properties = [
            'dimension_scores' => [
                'type' => 'OBJECT',
                'properties' => $dimensionProperties,
                'required' => array_keys($dimensionProperties),
            ],
            'feedback' => ['type' => 'STRING'],
            'confidence' => ['type' => 'STRING', 'enum' => self::CONFIDENCE_LEVELS],
        ];

        $required = ['dimension_scores', 'feedback', 'confidence'];

        if ($withTranscript) {
            $properties = ['transcript' => ['type' => 'STRING'], ...$properties];
            $required = ['transcript', ...$required];
        }

        return [
            'type' => 'OBJECT',
            'properties' => $properties,
            'required' => $required,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, int>
     */
    private function normalizeDimensions(array $result): array
    {
        $raw = $result['dimension_scores'] ?? null;

        if (! is_array($raw)) {
            throw new RuntimeException('Gemini response is missing dimension scores.');
        }

        $scores = [];

        foreach (WritingDimension::cases() as $dimension) {
            $value = $raw[$dimension->value] ?? null;

            if (! is_numeric($value)) {
                throw new RuntimeException(sprintf('Gemini response is missing the %s score.', $dimension->value));
            }

            $scores[$dimension->value] = max(1, min(5, (int) $value));
        }

        return $scores;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function stringField(array $result, string $key): ?string
    {
        $value = $result[$key] ?? null;

        return is_string($value) && mb_trim($value) !== '' ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function normalizeConfidence(array $result): string
    {
        $value = $result['confidence'] ?? null;

        return is_string($value) && in_array(mb_strtolower($value), self::CONFIDENCE_LEVELS, true)
            ? mb_strtolower($value)
            : 'low';
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function persist(PlacementAttempt $attempt, PlacementSection $section, array $attributes): PlacementAiDraft
    {
        return PlacementAiDraft::query()->updateOrCreate(
            ['placement_attempt_id' => $attempt->id, 'section' => $section],
            $attributes,
        );
    }
}
