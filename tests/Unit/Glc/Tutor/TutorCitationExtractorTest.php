<?php

declare(strict_types=1);

use App\Services\Glc\Tutor\TutorCitationExtractor;
use Tests\Fixtures\Glc\GeminiFake;

it('extracts unique titles from grounding metadata', function (): void {
    $extractor = new TutorCitationExtractor;

    $response = GeminiFake::chat('Hint only.', citations: ['Unit Worksheet', 'Grammar Guide', 'Unit Worksheet']);

    expect($extractor->titlesFromGroundingMetadata($response))->toBe(['Unit Worksheet', 'Grammar Guide']);
});

it('returns an empty list when grounding metadata is missing', function (): void {
    $extractor = new TutorCitationExtractor;

    expect($extractor->titlesFromGroundingMetadata(GeminiFake::text('Plain text.')))->toBe([]);
});
