<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum SpeakingDimension: string
{
    case Fluency = 'fluency';
    case Grammar = 'grammar';
    case Vocabulary = 'vocabulary';
    case TaskCompletion = 'task_completion';
    case Comprehensibility = 'comprehensibility';

    public function label(): string
    {
        return match ($this) {
            self::Fluency => 'Fluency & Coherence',
            self::Grammar => 'Grammar',
            self::Vocabulary => 'Vocabulary',
            self::TaskCompletion => 'Task Completion',
            self::Comprehensibility => 'Comprehensibility',
        };
    }
}
