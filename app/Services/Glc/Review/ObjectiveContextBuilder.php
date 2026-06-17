<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use Illuminate\Support\Collection;

final class ObjectiveContextBuilder
{
    public function __construct(private readonly ObjectiveAnswerGrader $grader) {}

    public function build(PlacementAttempt $attempt): string
    {
        $answersByItem = $attempt->answers()->get()->keyBy('placement_item_id');
        $blocks = [];

        foreach ($this->objectiveSections() as $section) {
            $items = $this->scoreableItems($section);

            if ($items->isEmpty()) {
                continue;
            }

            $summary = $this->sectionSummary($items, $answersByItem);

            $lines = [sprintf(
                '%s — auto-scored from the question bank: %d/%d correct (%s%%)',
                $section->label(),
                $summary['correct'],
                $summary['total'],
                $summary['percentage'],
            )];

            foreach ($items->values() as $index => $item) {
                $lines[] = $this->questionLine($index + 1, $item, $answersByItem);
            }

            $blocks[] = implode("\n", $lines);
        }

        if ($blocks === []) {
            return 'No objective sections were available for this attempt.';
        }

        return implode("\n\n", $blocks);
    }

    /**
     * @return array<string, array{correct: int, total: int, percentage: float}>
     */
    public function summary(PlacementAttempt $attempt): array
    {
        $answersByItem = $attempt->answers()->get()->keyBy('placement_item_id');
        $summaries = [];

        foreach ($this->objectiveSections() as $section) {
            $items = $this->scoreableItems($section);

            if ($items->isEmpty()) {
                continue;
            }

            $summaries[$section->value] = $this->sectionSummary($items, $answersByItem);
        }

        return $summaries;
    }

    /**
     * @return list<PlacementSection>
     */
    private function objectiveSections(): array
    {
        return array_values(array_filter(
            PlacementSection::ordered(),
            fn (PlacementSection $section): bool => $section->isObjective(),
        ));
    }

    /**
     * @return Collection<int, PlacementItem>
     */
    private function scoreableItems(PlacementSection $section): Collection
    {
        return PlacementItem::query()
            ->active()
            ->forSection($section)
            ->where('type', PlacementItemType::Question)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->filter(fn (PlacementItem $item): bool => $this->grader->isGradable($item))
            ->values();
    }

    /**
     * @param  Collection<int, PlacementItem>  $items
     * @param  Collection<int|string, \App\Models\Glc\PlacementAnswer>  $answersByItem
     * @return array{correct: int, total: int, percentage: float}
     */
    private function sectionSummary(Collection $items, Collection $answersByItem): array
    {
        $correct = $items->filter(
            fn (PlacementItem $item): bool => $this->grader->isCorrect($item, $answersByItem->get($item->id)?->response),
        )->count();

        return [
            'correct' => $correct,
            'total' => $items->count(),
            'percentage' => round(($correct / $items->count()) * 100, 2),
        ];
    }

    /**
     * @param  Collection<int|string, \App\Models\Glc\PlacementAnswer>  $answersByItem
     */
    private function questionLine(int $number, PlacementItem $item, Collection $answersByItem): string
    {
        if ($this->grader->isGapFill($item)) {
            return $this->gapFillLine($number, $item, $answersByItem);
        }

        $selected = $this->selectedOption($item, $answersByItem);
        $options = collect($item->options ?? [])
            ->map(fn (string $option, int $index): string => sprintf('%s) %s', $this->optionLetter($index), $option))
            ->implode(' ');

        return sprintf(
            'Q%d: %s | Options: %s | Candidate answered: %s | Correct answer: %s | %s',
            $number,
            mb_trim((string) $item->body),
            $options !== '' ? $options : 'n/a',
            $selected === null ? 'no answer' : $this->optionLabel($item, $selected),
            $this->optionLabel($item, (int) $item->correct_option),
            $selected === $item->correct_option ? 'correct' : 'incorrect',
        );
    }

    /**
     * @param  Collection<int|string, \App\Models\Glc\PlacementAnswer>  $answersByItem
     */
    private function gapFillLine(int $number, PlacementItem $item, Collection $answersByItem): string
    {
        $response = $answersByItem->get($item->id)?->response;
        $text = $response['text'] ?? null;
        $accepted = collect($item->settings['accepted_answers'] ?? [])
            ->filter(fn (mixed $answer): bool => is_string($answer))
            ->implode(' / ');

        return sprintf(
            'Q%d (gap fill): %s | Accepted answers: %s | Candidate answered: %s | %s',
            $number,
            mb_trim((string) $item->body),
            $accepted !== '' ? $accepted : 'n/a',
            is_string($text) && mb_trim($text) !== '' ? mb_trim($text) : 'no answer',
            $this->grader->isCorrect($item, $response) ? 'correct' : 'incorrect',
        );
    }

    /**
     * @param  Collection<int|string, \App\Models\Glc\PlacementAnswer>  $answersByItem
     */
    private function selectedOption(PlacementItem $item, Collection $answersByItem): ?int
    {
        $selected = $answersByItem->get($item->id)?->response['selected'] ?? null;

        return is_numeric($selected) ? (int) $selected : null;
    }

    private function optionLabel(PlacementItem $item, int $index): string
    {
        $text = $item->options[$index] ?? null;

        return $text === null
            ? $this->optionLetter($index)
            : sprintf('%s) %s', $this->optionLetter($index), $text);
    }

    private function optionLetter(int $index): string
    {
        return chr(65 + $index);
    }
}
