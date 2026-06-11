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
