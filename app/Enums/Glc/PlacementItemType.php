<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum PlacementItemType: string
{
    case Passage = 'passage';
    case Question = 'question';
    case AudioClip = 'audio_clip';
    case Prompt = 'prompt';

    public function label(): string
    {
        return match ($this) {
            self::Passage => 'Passage',
            self::Question => 'Question',
            self::AudioClip => 'Audio Clip',
            self::Prompt => 'Prompt',
        };
    }
}
