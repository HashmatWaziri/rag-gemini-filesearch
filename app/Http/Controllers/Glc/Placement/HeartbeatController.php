<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Placement;

use App\Services\Glc\Placement\PlacementSessionService;
use App\Services\Glc\Placement\SectionTimerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class HeartbeatController
{
    public function __construct(
        private PlacementSessionService $sessions,
        private SectionTimerService $timer,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $attempt = $this->sessions->requireActiveAttempt($request);

        if ($this->sessions->expectedRouteName($attempt) !== 'placement.test') {
            return response()->json([
                'message' => 'No section is currently running.',
                'redirect' => route($this->sessions->expectedRouteName($attempt)),
            ], 409);
        }

        $sync = $this->timer->syncCurrentSection($attempt);

        if ($sync['finished']) {
            $this->sessions->finalizeSubmission($attempt);

            return response()->json([
                'redirect' => route('placement.complete'),
                'sectionCompleted' => true,
            ]);
        }

        if ($sync['advanced']) {
            return response()->json([
                'redirect' => route('placement.test'),
                'sectionCompleted' => true,
            ]);
        }

        return response()->json([
            'remainingSeconds' => $sync['state']->remainingSeconds(),
            'timeUsedSeconds' => $sync['state']->time_used_seconds,
            'sectionCompleted' => false,
        ]);
    }
}
