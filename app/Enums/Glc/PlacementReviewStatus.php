<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum PlacementReviewStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Sent = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for review',
            self::InReview => 'Being reviewed',
            self::Approved => 'Ready to send',
            self::Sent => 'Result sent',
        };
    }
}
