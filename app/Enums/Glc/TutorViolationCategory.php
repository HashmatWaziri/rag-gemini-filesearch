<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum TutorViolationCategory: string
{
    case DirectAnswerSeeking = 'direct_answer_seeking';
    case OffTopic = 'off_topic';
    case PersonalSocial = 'personal_social';
    case Inappropriate = 'inappropriate';
    case OutOfScopeUnit = 'out_of_scope_unit';
    case SpeakingListeningRequest = 'speaking_listening_request';

    public function label(): string
    {
        return match ($this) {
            self::DirectAnswerSeeking => 'Direct Answer Seeking',
            self::OffTopic => 'Off Topic',
            self::PersonalSocial => 'Personal / Social',
            self::Inappropriate => 'Inappropriate',
            self::OutOfScopeUnit => 'Future / Unassigned Unit',
            self::SpeakingListeningRequest => 'Speaking / Listening Practice Request',
        };
    }
}
