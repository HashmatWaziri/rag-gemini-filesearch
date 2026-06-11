<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Placement;

use App\Enums\Glc\PlacementAttemptStatus;
use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Services\Glc\Placement\PlacementSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ListeningAudioController
{
    public function __construct(private PlacementSessionService $sessions) {}

    public function registerPlay(Request $request, PlacementItem $item): JsonResponse
    {
        $attempt = $this->sessions->requireActiveAttempt($request);

        if (! $this->isPlayableClip($item) || $attempt->current_section !== PlacementSection::Listening) {
            return response()->json(['message' => 'This clip is not available.'], 422);
        }

        $state = $this->sessions->sectionState($attempt, PlacementSection::Listening);

        if ($state->status !== PlacementSectionStatus::InProgress) {
            return response()->json(['message' => 'The Listening section is not in progress.'], 409);
        }

        $play = $attempt->audioPlays()->firstOrCreate(
            ['placement_item_id' => $item->id],
            ['played_at' => now()],
        );

        if (! $play->wasRecentlyCreated) {
            return response()->json([
                'message' => 'This clip has already been played. Each clip can only be played once.',
                'played' => true,
            ], 403);
        }

        return response()->json([
            'url' => URL::temporarySignedRoute(
                'placement.listening.stream',
                now()->addMinutes(10),
                ['item' => $item->id],
            ),
        ]);
    }

    public function stream(Request $request, PlacementItem $item): StreamedResponse
    {
        abort_unless($request->hasValidSignature(), 403, 'This audio link has expired or does not look right. Please go back to your test.');

        $attempt = $this->sessions->currentAttempt($request);

        abort_unless($attempt instanceof PlacementAttempt, 403, 'We could not find your test on this device. Please enter your access code again.');
        abort_unless($attempt->status === PlacementAttemptStatus::InProgress, 403, 'This test is no longer in progress.');

        $played = $attempt->audioPlays()->where('placement_item_id', $item->id)->exists();
        abort_unless($played, 403, 'This clip has not been unlocked for playback.');

        abort_unless($this->isPlayableClip($item) && is_string($item->media_path), 404);
        abort_unless(Storage::disk('local')->exists($item->media_path), 404);

        return Storage::disk('local')->response($item->media_path);
    }

    private function isPlayableClip(PlacementItem $item): bool
    {
        return $item->is_active
            && $item->type === PlacementItemType::AudioClip
            && $item->section === PlacementSection::Listening;
    }
}
