<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum CurriculumMaterialKind: string
{
    case Summary = 'summary';
    case Notes = 'notes';
    case Worksheet = 'worksheet';
    case ApprovedPdf = 'approved_pdf';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Summary => 'GLC summary',
            self::Notes => 'GLC notes',
            self::Worksheet => 'Worksheet',
            self::ApprovedPdf => 'Approved PDF',
            self::Other => 'Other',
        };
    }
}
