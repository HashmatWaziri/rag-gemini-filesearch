<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use App\Models\Glc\PlacementReview;
use App\Services\Glc\Review\ResultPdfRenderer;
use Illuminate\Http\Response;

final readonly class ResultPdfController
{
    public function __construct(private ResultPdfRenderer $renderer) {}

    public function show(PlacementReview $review): Response
    {
        abort_unless($review->canGeneratePdf(), 403, 'The PDF is available once the review and narrative are approved.');

        return $this->renderer->pdf($review)->stream('placement-test-result.pdf');
    }
}
