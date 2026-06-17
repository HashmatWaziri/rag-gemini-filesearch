<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use App\Enums\Glc\PlacementAiDraftStatus;
use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\SpeakingDimension;
use App\Enums\Glc\WritingDimension;
use App\Models\Glc\PlacementAiDraft;
use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementAttempt;
use App\Services\Glc\Admin\SpeakingEvaluationGuidelines;
use App\Services\Glc\Admin\WritingEvaluationGuidelines;
use App\Services\Glc\Ai\GlcAiCostGuard;
use App\Services\Glc\Ai\PlacementAiSettings;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Laravel\Ai\Transcription;
use RuntimeException;
use Throwable;

final readonly class AiDraftService
{
    public function __construct(
        private PlacementAiSettings $settings,
        private WritingEvaluationGuidelines $writingGuidelines,
        private SpeakingEvaluationGuidelines $speakingGuidelines,
        private ObjectiveContextBuilder $objectiveContext,
        private GlcAiCostGuard $costGuard,
    ) {}

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
        } catch (SpeakingEvaluationFailed $exception) {
            return $this->persist($attempt, $section, [
                'dimension_scores' => null,
                'transcript' => $exception->transcript,
                'feedback' => null,
                'confidence' => null,
                'status' => PlacementAiDraftStatus::Failed,
                'error' => mb_substr($exception->getMessage(), 0, 1000),
                'generated_at' => null,
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

        $this->costGuard->assertWithinLimits();
        $this->settings->hydrateProviderConfig();
        $selection = $this->settings->selection(PlacementAiSettings::TASK_WRITING);

        $response = new WritingEvaluationAgent()->prompt(
            $this->writingPrompt($attempt, $essay),
            provider: $selection['provider'],
            model: $selection['model'],
        );

        return [
            ...$this->normalizeEvaluation($this->structuredResult($response), WritingDimension::cases()),
            'transcript' => null,
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

        $transcript = $this->transcribe($audioPath);

        try {
            $this->costGuard->assertWithinLimits();
            $selection = $this->settings->selection(PlacementAiSettings::TASK_SPEAKING_EVALUATION);

            $response = new SpeakingEvaluationAgent()->prompt(
                $this->speakingPrompt($attempt, $transcript),
                provider: $selection['provider'],
                model: $selection['model'],
            );

            return [
                ...$this->normalizeEvaluation($this->structuredResult($response), SpeakingDimension::cases()),
                'transcript' => $transcript,
            ];
        } catch (Throwable $exception) {
            throw new SpeakingEvaluationFailed($transcript, $exception);
        }
    }

    private function writingPrompt(PlacementAttempt $attempt, string $essay): string
    {
        return implode("\n\n", [
            "Evaluate the candidate essay against these GLC writing evaluation guidelines:\n".$this->writingGuidelines->asPromptBlock(),
            "Candidate's objective-section performance for context — do not let it override your assessment of the essay itself:\n".$this->objectiveContext->build($attempt),
            "Candidate essay:\n".$essay,
        ]);
    }

    private function speakingPrompt(PlacementAttempt $attempt, string $transcript): string
    {
        return implode("\n\n", [
            "Evaluate the candidate's spoken response transcript against these GLC speaking evaluation guidelines:\n".$this->speakingGuidelines->asPromptBlock(),
            "Candidate's objective-section performance for context — do not let it override your assessment of the spoken response itself:\n".$this->objectiveContext->build($attempt),
            "Candidate speaking transcript:\n".$transcript,
        ]);
    }

    private function transcribe(string $audioPath): string
    {
        $this->costGuard->assertWithinLimits();
        $this->settings->hydrateProviderConfig();
        $selection = $this->settings->selection(PlacementAiSettings::TASK_SPEAKING);

        $response = Transcription::fromStorage($audioPath, 'local')->generate(
            provider: $selection['provider'],
            model: $selection['model'],
        );

        $transcript = mb_trim($response->text);

        if ($transcript === '') {
            throw new RuntimeException('Transcription produced an empty transcript for this recording.');
        }

        return $transcript;
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  list<WritingDimension|SpeakingDimension>  $dimensions
     * @return array{dimension_scores: array<string, int>, feedback: string|null, confidence: string}
     */
    private function normalizeEvaluation(array $result, array $dimensions): array
    {
        return [
            'dimension_scores' => $this->normalizeDimensions($result, $dimensions),
            'feedback' => $this->stringField($result, 'feedback'),
            'confidence' => $this->normalizeConfidence($result),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function structuredResult(AgentResponse $response): array
    {
        if ($response instanceof StructuredAgentResponse) {
            return $response->structured;
        }

        $decoded = json_decode($response->text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('The AI evaluation response could not be parsed as JSON.');
        }

        return $decoded;
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
     * @param  array<string, mixed>  $result
     * @param  list<WritingDimension|SpeakingDimension>  $dimensions
     * @return array<string, int>
     */
    private function normalizeDimensions(array $result, array $dimensions): array
    {
        $raw = $result['dimension_scores'] ?? null;

        if (! is_array($raw)) {
            throw new RuntimeException('The AI evaluation response is missing dimension scores.');
        }

        $scores = [];

        foreach ($dimensions as $dimension) {
            $value = $raw[$dimension->value] ?? null;

            if (! is_numeric($value)) {
                throw new RuntimeException(sprintf('The AI evaluation response is missing the %s score.', $dimension->value));
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

        return is_string($value) && in_array(mb_strtolower($value), WritingEvaluationAgent::CONFIDENCE_LEVELS, true)
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
