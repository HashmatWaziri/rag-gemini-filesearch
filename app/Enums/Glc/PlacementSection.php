<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum PlacementSection: string
{
    case Reading = 'reading';
    case GrammarVocabulary = 'grammar_vocabulary';
    case Listening = 'listening';
    case Writing = 'writing';
    case Speaking = 'speaking';

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::Reading,
            self::GrammarVocabulary,
            self::Listening,
            self::Writing,
            self::Speaking,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::Reading => 'Reading',
            self::GrammarVocabulary => 'Grammar & Vocabulary',
            self::Listening => 'Listening',
            self::Writing => 'Writing',
            self::Speaking => 'Speaking',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::Reading => 1,
            self::GrammarVocabulary => 2,
            self::Listening => 3,
            self::Writing => 4,
            self::Speaking => 5,
        };
    }

    public function next(): ?self
    {
        $ordered = self::ordered();
        $index = array_search($this, $ordered, true);

        return $ordered[$index + 1] ?? null;
    }

    public function previous(): ?self
    {
        $ordered = self::ordered();
        $index = array_search($this, $ordered, true);

        return $index > 0 ? $ordered[$index - 1] : null;
    }

    public function isFirst(): bool
    {
        return $this === self::Reading;
    }

    public function isLast(): bool
    {
        return $this === self::Speaking;
    }

    public function isObjective(): bool
    {
        return match ($this) {
            self::Reading, self::GrammarVocabulary, self::Listening => true,
            self::Writing, self::Speaking => false,
        };
    }

    public function timeLimitSeconds(): int
    {
        return config()->integer('glc.placement.section_time_limits.'.$this->value, 900);
    }
}
