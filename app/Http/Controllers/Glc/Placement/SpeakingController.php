<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Placement;

use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Models\Glc\PlacementItem;
use App\Services\Glc\Placement\PlacementContentService;
use App\Services\Glc\Placement\PlacementSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

final readonly class SpeakingController
{
    public function __construct(
        private PlacementSessionService $sessions,
        private PlacementContentService $content,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $attempt = $this->sessions->requireActiveAttempt($request);

        if ($attempt->current_section !== PlacementSection::Speaking) {
            return response()->json(['message' => 'The Speaking section is not your current section.'], 409);
        }

        $state = $this->sessions->sectionState($attempt, PlacementSection::Speaking);

        if ($state->status !== PlacementSectionStatus::InProgress) {
            return response()->json(['message' => 'This section is not in progress.'], 409);
        }

        $prompt = $this->content->promptItem(PlacementSection::Speaking);

        if (! $prompt instanceof PlacementItem) {
            return response()->json(['message' => 'No speaking prompt is configured.'], 422);
        }

        $settings = $this->content->speakingPromptPayload();
        $maxDuration = $settings['maxDurationSeconds'];
        $maxAttempts = $settings['maxAttempts'];

        $request->validate([
            'quality_passed' => ['required', 'boolean'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:'.($maxDuration + 5)],
            'audio' => [
                Rule::requiredIf($request->boolean('quality_passed')),
                'file',
                'max:25600',
                'mimetypes:audio/webm,video/webm,audio/ogg,application/ogg,audio/mp4,video/mp4,audio/mpeg,audio/aac,audio/x-m4a',
            ],
        ], [
            'audio.required' => 'We could not find your recording. Please record again.',
            'audio.file' => 'That recording does not look right. Please record again.',
            'audio.max' => 'Your recording is too large to save. Please record a shorter one.',
            'audio.mimetypes' => 'That recording does not look right. Please record again.',
            'duration_seconds.required' => 'The recording length does not look right. Please record again.',
            'duration_seconds.integer' => 'The recording length does not look right. Please record again.',
            'duration_seconds.min' => 'The recording length does not look right. Please record again.',
            'duration_seconds.max' => 'Your recording is longer than allowed. Please record a shorter one.',
        ]);

        $answer = $attempt->answers()->firstOrNew(['placement_item_id' => $prompt->id]);
        $attemptsUsed = (int) ($answer->recording_attempts ?? 0);

        if (! $request->boolean('quality_passed')) {
            return response()->json([
                'counted' => false,
                'attemptsUsed' => $attemptsUsed,
                'attemptsRemaining' => max(0, $maxAttempts - $attemptsUsed),
                'message' => 'The recording did not pass the quality check. This try does not count - please record again.',
            ]);
        }

        if ($attemptsUsed >= $maxAttempts) {
            return response()->json([
                'message' => "You have used all {$maxAttempts} recording attempts.",
                'attemptsUsed' => $attemptsUsed,
                'attemptsRemaining' => 0,
            ], 422);
        }

        $file = $request->file('audio');

        try {
            $path = $file->store('glc/placement/recordings/'.$attempt->id, 'local');
        } catch (Throwable $exception) {
            report($exception);
            $path = false;
        }

        if ($path === false) {
            return response()->json([
                'message' => 'Something went wrong on our side and your recording was not saved. This try was not counted - please try again, and contact GLC if it keeps happening.',
            ], 500);
        }

        $answer->fill([
            'response' => [
                'audio_path' => $path,
                'duration_seconds' => (int) $request->integer('duration_seconds'),
                'mime_type' => $file->getMimeType(),
            ],
            'recording_attempts' => $attemptsUsed + 1,
        ])->save();

        return response()->json([
            'counted' => true,
            'attemptsUsed' => $attemptsUsed + 1,
            'attemptsRemaining' => max(0, $maxAttempts - $attemptsUsed - 1),
            'durationSeconds' => (int) $request->integer('duration_seconds'),
        ]);
    }
}
