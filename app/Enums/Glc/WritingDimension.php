<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum WritingDimension: string
{
    case Grammar = 'grammar';
    case Vocabulary = 'vocabulary';
    case Structure = 'structure';
    case Coherence = 'coherence';
    case TaskCompletion = 'task_completion';

    public function label(): string
    {
        return match ($this) {
            self::Grammar => 'Grammar',
            self::Vocabulary => 'Vocabulary',
            self::Structure => 'Structure',
            self::Coherence => 'Coherence',
            self::TaskCompletion => 'Task Completion',
        };
    }
}
