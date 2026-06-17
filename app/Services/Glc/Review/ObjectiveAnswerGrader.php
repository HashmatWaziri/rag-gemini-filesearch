<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use App\Enums\Glc\PlacementItemType;
use App\Models\Glc\PlacementItem;

final class ObjectiveAnswerGrader
{
    public function isGradable(PlacementItem $item): bool
    {
        if ($item->type !== PlacementItemType::Question) {
            return false;
        }

        return $this->isGapFill($item) || $item->correct_option !== null;
    }

    public function isGapFill(PlacementItem $item): bool
    {
        if (($item->settings['format'] ?? null) !== 'gap_fill') {
            return false;
        }

        $accepted = $item->settings['accepted_answers'] ?? null;

        return is_array($accepted) && $accepted !== [];
    }

    /**
     * @param  array<string, mixed>|null  $response
     */
    public function isCorrect(PlacementItem $item, ?array $response): bool
    {
        if ($this->isGapFill($item)) {
            return $this->matchesAcceptedAnswer($item, $response['text'] ?? null);
        }

        $selected = $response['selected'] ?? null;

        return is_numeric($selected) && (int) $selected === $item->correct_option;
    }

    private function matchesAcceptedAnswer(PlacementItem $item, mixed $text): bool
    {
        if (! is_string($text)) {
            return false;
        }

        $normalized = $this->normalize($text);

        if ($normalized === '') {
            return false;
        }

        foreach ($item->settings['accepted_answers'] ?? [] as $accepted) {
            if (is_string($accepted) && $this->normalize($accepted) === $normalized) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $text): string
    {
        $collapsed = (string) preg_replace('/\s+/u', ' ', $text);

        return mb_strtolower(mb_trim($collapsed));
    }
}
