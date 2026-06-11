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
            self::Pending => 'Pending Review',
            self::InReview => 'In Review',
            self::Approved => 'Approved',
            self::Sent => 'Result Sent',
        };
    }
}
