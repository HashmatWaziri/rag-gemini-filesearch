<?php

declare(strict_types=1);

namespace App\Services\Glc\Placement;

use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;

final class PlacementContentService
{
    public static function countWords(string $text): int
    {
        $words = preg_split('/\s+/u', mb_trim($text), -1, PREG_SPLIT_NO_EMPTY);

        return $words === false ? 0 : count($words);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readingPayload(): array
    {
        return PlacementItem::query()
            ->active()
            ->forSection(PlacementSection::Reading)
            ->where('type', PlacementItemType::Passage)
            ->whereNull('parent_id')
            ->orderBy('position')
            ->with('children')
            ->get()
            ->map(fn (PlacementItem $passage): array => [
                'id' => $passage->id,
                'position' => $passage->position,
                'title' => $passage->title,
                'body' => $passage->body,
                'questions' => $passage->children
                    ->filter(fn (PlacementItem $child): bool => $child->is_active && $child->type === PlacementItemType::Question)
                    ->map(fn (PlacementItem $question): array => $this->questionPayload($question))
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function grammarVocabularyPayload(): array
    {
        return PlacementItem::query()
            ->active()
            ->forSection(PlacementSection::GrammarVocabulary)
            ->where('type', PlacementItemType::Question)
            ->whereNull('parent_id')
            ->orderBy('position')
            ->get()
            ->map(fn (PlacementItem $question): array => $this->questionPayload($question))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listeningPayload(PlacementAttempt $attempt): array
    {
        $playedItemIds = $attempt->audioPlays()->pluck('placement_item_id')->all();

        return PlacementItem::query()
            ->active()
            ->forSection(PlacementSection::Listening)
            ->where('type', PlacementItemType::AudioClip)
            ->whereNull('parent_id')
            ->orderBy('position')
            ->with('children')
            ->get()
            ->map(fn (PlacementItem $clip): array => [
                'id' => $clip->id,
                'position' => $clip->position,
                'title' => $clip->title,
                'played' => in_array($clip->id, $playedItemIds, true),
                'questions' => $clip->children
                    ->filter(fn (PlacementItem $child): bool => $child->is_active && $child->type === PlacementItemType::Question)
                    ->map(fn (PlacementItem $question): array => $this->questionPayload($question))
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function writingPromptPayload(): ?array
    {
        $prompt = $this->promptItem(PlacementSection::Writing);

        if (! $prompt instanceof PlacementItem) {
            return null;
        }

        return [
            'id' => $prompt->id,
            'title' => $prompt->title,
            'body' => $prompt->body,
            'minWords' => (int) ($prompt->settings['min_words'] ?? config()->integer('glc.placement.writing.min_words', 150)),
            'maxWords' => (int) ($prompt->settings['max_words'] ?? config()->integer('glc.placement.writing.max_words', 250)),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function speakingPromptPayload(): ?array
    {
        $prompt = $this->promptItem(PlacementSection::Speaking);

        if (! $prompt instanceof PlacementItem) {
            return null;
        }

        return [
            'id' => $prompt->id,
            'title' => $prompt->title,
            'body' => $prompt->body,
            'maxDurationSeconds' => (int) ($prompt->settings['max_duration_seconds'] ?? config()->integer('glc.placement.speaking.max_duration_seconds', 180)),
            'maxAttempts' => (int) ($prompt->settings['max_attempts'] ?? config()->integer('glc.placement.speaking.max_attempts', 3)),
        ];
    }

    public function promptItem(PlacementSection $section): ?PlacementItem
    {
        return PlacementItem::query()
            ->active()
            ->forSection($section)
            ->where('type', PlacementItemType::Prompt)
            ->orderBy('position')
            ->first();
    }

    /**
     * @return array<int, int>
     */
    public function savedSelections(PlacementAttempt $attempt, PlacementSection $section): array
    {
        return $attempt->answers()
            ->whereHas('item', function ($query) use ($section): void {
                $query->where('section', $section)->where('type', PlacementItemType::Question);
            })
            ->get()
            ->mapWithKeys(function ($answer): array {
                $selected = $answer->response['selected'] ?? null;

                return is_int($selected) ? [$answer->placement_item_id => $selected] : [];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function questionPayload(PlacementItem $question): array
    {
        return [
            'id' => $question->id,
            'position' => $question->position,
            'body' => $question->body,
            'options' => $question->options ?? [],
        ];
    }
}
