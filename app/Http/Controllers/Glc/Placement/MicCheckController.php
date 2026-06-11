<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Placement;

use App\Services\Glc\Placement\MicCheckTranscriber;
use App\Services\Glc\Placement\PlacementSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class MicCheckController
{
    public function __construct(
        private PlacementSessionService $sessions,
        private MicCheckTranscriber $transcriber,
    ) {}

    public function transcribe(Request $request): JsonResponse
    {
        $attempt = $this->sessions->requireActiveAttempt($request);

        if ($this->sessions->expectedRouteName($attempt) !== 'placement.device-check') {
            return response()->json([
                'message' => 'The microphone check is only available during audio setup.',
            ], 409);
        }

        $maxSeconds = config()->integer('glc.placement.device_check.recording_max_seconds', 10);
        $maxKilobytes = config()->integer('glc.placement.device_check.recording_max_kilobytes', 5120);

        $request->validate([
            'audio' => [
                'required',
                'file',
                'max:'.$maxKilobytes,
                'mimetypes:audio/webm,video/webm,audio/ogg,application/ogg,audio/mp4,video/mp4,audio/mpeg,audio/aac,audio/x-m4a,audio/wav,audio/x-wav',
            ],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:'.($maxSeconds + 2)],
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

        $file = $request->file('audio');
        $transcript = $this->transcriber->transcribe(
            $file->getContent(),
            $file->getMimeType() ?? 'audio/webm',
        );

        return response()->json([
            'transcript' => $transcript,
            'transcriptionAvailable' => $transcript !== null,
        ]);
    }
}
