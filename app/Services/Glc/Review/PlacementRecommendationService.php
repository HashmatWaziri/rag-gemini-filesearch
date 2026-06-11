<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementAiDraftStatus;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAiRecommendation;
use App\Models\Glc\PlacementAttempt;
use App\Services\Glc\Admin\SpeakingEvaluationGuidelines;
use App\Services\Glc\Admin\WritingEvaluationGuidelines;
use App\Services\Glc\Ai\PlacementAiSettings;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;
use Throwable;

final readonly class PlacementRecommendationService
{
    public function __construct(
        private PlacementAiSettings $settings,
        private WritingEvaluationGuidelines $writingGuidelines,
        private SpeakingEvaluationGuidelines $speakingGuidelines,
        private ObjectiveContextBuilder $objectiveContext,
    ) {}

    public function recommend(PlacementAttempt $attempt): PlacementAiRecommendation
    {
        try {
            $this->settings->hydrateProviderConfig();
            $selection = $this->settings->selection(PlacementAiSettings::TASK_WRITING);

            $response = new PlacementRecommendationAgent()->prompt(
                $this->prompt($attempt),
                provider: $selection['provider'],
                model: $selection['model'],
            );

            $result = $this->structuredResult($response);

            return $this->persist($attempt, [
                'status' => PlacementAiDraftStatus::Completed,
                'recommended_level' => $this->normalizeLevel($result['recommended_level'] ?? null),
                'skill_levels' => $this->normalizePerSkill($result['skill_levels'] ?? null, levels: true),
                'skill_summaries' => $this->normalizePerSkill($result['skill_summaries'] ?? null, levels: false),
                'confidence' => $this->normalizeConfidence($result),
                'rationale' => $this->stringField($result, 'rationale'),
                'error' => null,
                'generated_at' => now(),
            ]);
        } catch (Throwable $exception) {
            return $this->persist($attempt, [
                'status' => PlacementAiDraftStatus::Failed,
                'recommended_level' => null,
                'skill_levels' => null,
                'skill_summaries' => null,
                'confidence' => null,
                'rationale' => null,
                'error' => mb_substr($exception->getMessage(), 0, 1000),
                'generated_at' => null,
            ]);
        }
    }

    private function prompt(PlacementAttempt $attempt): string
    {
        $blocks = [
            "GLC proficiency levels, from lowest to highest, with the planning percentage bands used for auto-scored sections:\n".$this->levelGuide(),
            "Auto-scored objective sections (Reading, Grammar & Vocabulary, Listening):\n".$this->objectiveContext->build($attempt),
            "GLC writing evaluation guidelines:\n".$this->writingGuidelines->asPromptBlock(),
            "GLC speaking evaluation guidelines:\n".$this->speakingGuidelines->asPromptBlock(),
        ];

        foreach ([PlacementSection::Writing, PlacementSection::Speaking] as $section) {
            $blocks[] = sprintf("%s AI evaluation result:\n%s", $section->label(), $this->draftBlock($attempt, $section));
        }

        $blocks[] = 'Produce the staff-only provisional recommendation now.';

        return implode("\n\n", $blocks);
    }

    private function levelGuide(): string
    {
        $bands = [
            GlcLevel::Starter->value => '0-14%',
            GlcLevel::Beginner->value => '15-29%',
            GlcLevel::Elementary->value => '30-44%',
            GlcLevel::PreIntermediate->value => '45-59%',
            GlcLevel::Intermediate->value => '60-74%',
            GlcLevel::UpperIntermediate->value => '75-89%',
            GlcLevel::Advanced->value => '90-100%',
        ];

        $lines = [];

        foreach (GlcLevel::cases() as $level) {
            $lines[] = sprintf('- %s (%s): approx. %s', $level->label(), $level->value, $bands[$level->value]);
        }

        return implode("\n", $lines);
    }

    private function draftBlock(PlacementAttempt $attempt, PlacementSection $section): string
    {
        $draft = $attempt->aiDrafts()->where('section', $section)->first();

        if ($draft === null) {
            return 'No AI evaluation is available for this section.';
        }

        if ($draft->status !== PlacementAiDraftStatus::Completed) {
            $lines = ['The AI evaluation for this section failed.'];

            if (is_string($draft->transcript) && $draft->transcript !== '') {
                $lines[] = 'Transcript of the candidate response:'."\n".$draft->transcript;
            }

            return implode("\n", $lines);
        }

        $lines = [];

        foreach ($draft->dimension_scores ?? [] as $dimension => $score) {
            $lines[] = sprintf('- %s: %d/5', $dimension, $score);
        }

        if (is_string($draft->feedback) && $draft->feedback !== '') {
            $lines[] = 'Evaluator feedback: '.$draft->feedback;
        }

        if (is_string($draft->confidence) && $draft->confidence !== '') {
            $lines[] = 'Evaluator confidence: '.$draft->confidence;
        }

        if (is_string($draft->transcript) && $draft->transcript !== '') {
            $lines[] = 'Transcript of the candidate response:'."\n".$draft->transcript;
        }

        return $lines === [] ? 'No usable evaluation detail was produced.' : implode("\n", $lines);
    }

    private function normalizeLevel(mixed $value): GlcLevel
    {
        $level = is_string($value) ? GlcLevel::tryFrom($value) : null;

        if ($level === null) {
            throw new RuntimeException('The AI recommendation is missing a valid overall GLC level.');
        }

        return $level;
    }

    /**
     * @return array<string, string>
     */
    private function normalizePerSkill(mixed $raw, bool $levels): array
    {
        if (! is_array($raw)) {
            throw new RuntimeException(sprintf('The AI recommendation is missing per-skill %s.', $levels ? 'levels' : 'summaries'));
        }

        $normalized = [];

        foreach (PlacementSection::ordered() as $section) {
            $value = $raw[$section->value] ?? null;

            if ($levels) {
                $level = is_string($value) ? GlcLevel::tryFrom($value) : null;

                if ($level === null) {
                    throw new RuntimeException(sprintf('The AI recommendation is missing a valid %s level.', $section->value));
                }

                $normalized[$section->value] = $level->value;

                continue;
            }

            if (! is_string($value) || mb_trim($value) === '') {
                throw new RuntimeException(sprintf('The AI recommendation is missing the %s summary.', $section->value));
            }

            $normalized[$section->value] = mb_trim($value);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function normalizeConfidence(array $result): string
    {
        $value = $result['confidence'] ?? null;

        return is_string($value) && in_array(mb_strtolower($value), PlacementRecommendationAgent::CONFIDENCE_LEVELS, true)
            ? mb_strtolower($value)
            : 'low';
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function stringField(array $result, string $key): ?string
    {
        $value = $result[$key] ?? null;

        return is_string($value) && mb_trim($value) !== '' ? mb_trim($value) : null;
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
            throw new RuntimeException('The AI recommendation response could not be parsed as JSON.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function persist(PlacementAttempt $attempt, array $attributes): PlacementAiRecommendation
    {
        return PlacementAiRecommendation::query()->updateOrCreate(
            ['placement_attempt_id' => $attempt->id],
            $attributes,
        );
    }
}
