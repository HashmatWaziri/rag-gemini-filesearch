<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Enums\Glc\WritingDimension;
use App\Exceptions\Glc\GlcAiCostLimitExceededException;
use App\Models\Glc\WritingSubmission;
use App\Services\Glc\Ai\GlcAiCostGuard;
use App\Services\Glc\Ai\PlacementAiSettings;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

final class WritingCorrectionService
{
    public const FAILURE_MESSAGE = 'We could not evaluate your writing right now. Please try submitting it again later.';

    public function __construct(
        private readonly PlacementAiSettings $aiSettings,
        private readonly GlcAiCostGuard $costGuard,
    ) {}

    public function evaluate(WritingSubmission $submission): void
    {
        if (! $this->aiSettings->taskIsConfigured(PlacementAiSettings::TASK_TUTOR_WRITING)) {
            $this->markFailed($submission);

            return;
        }

        try {
            $this->costGuard->assertWithinLimits();
            $this->aiSettings->hydrateProviderConfig();
            $selection = $this->aiSettings->selection(PlacementAiSettings::TASK_TUTOR_WRITING);

            $response = new TutorWritingCorrectionAgent()->prompt(
                "Evaluate this student writing:\n\n".$submission->text,
                provider: $selection['provider'],
                model: $selection['model'],
            );

            if (! $response instanceof StructuredAgentResponse) {
                $this->markFailed($submission);

                return;
            }

            $decoded = $response->toArray();
            $dimensions = $this->normalizeDimensions($decoded);

            if ($dimensions === null) {
                $this->markFailed($submission);

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
        } catch (GlcAiCostLimitExceededException) {
            $submission->update([
                'status' => 'failed',
                'error' => self::FAILURE_MESSAGE,
            ]);
        } catch (Throwable) {
            $this->markFailed($submission);
        }
    }

    private function markFailed(WritingSubmission $submission): void
    {
        $submission->update([
            'status' => 'failed',
            'error' => self::FAILURE_MESSAGE,
        ]);
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
