<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementReview;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class PlacementMediaController
{
    public function itemAudio(PlacementItem $item): StreamedResponse
    {
        abort_unless($item->type === PlacementItemType::AudioClip && $item->media_path !== null, 404);
        abort_unless(Storage::disk('local')->exists($item->media_path), 404);

        return Storage::disk('local')->response($item->media_path);
    }

    public function recording(PlacementReview $review): StreamedResponse
    {
        $answer = $review->attempt->answers()
            ->whereHas('item', function ($query): void {
                $query->where('section', PlacementSection::Speaking)->where('type', PlacementItemType::Prompt);
            })
            ->first();

        $path = $answer?->response['audio_path'] ?? null;

        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
