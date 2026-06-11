<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Placement;

use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAttempt;
use App\Services\Glc\Placement\PlacementSessionService;
use App\Services\Glc\Placement\SectionTimerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OnboardingController
{
    public function __construct(
        private PlacementSessionService $sessions,
        private SectionTimerService $timer,
    ) {}

    public function instructions(Request $request): Response|RedirectResponse
    {
        $attempt = $this->sessions->currentAttempt($request);

        if (! $attempt instanceof PlacementAttempt) {
            return redirect()->route('placement.entry');
        }

        if (($expected = $this->sessions->expectedRouteName($attempt)) !== 'placement.instructions') {
            return redirect()->route($expected);
        }

        return Inertia::render('glc/placement/instructions', [
            'candidateName' => $attempt->candidate_name,
            'sections' => collect(PlacementSection::ordered())->map(fn (PlacementSection $section): array => [
                'value' => $section->value,
                'label' => $section->label(),
                'order' => $section->order(),
                'estimatedMinutes' => intdiv($section->timeLimitSeconds(), 60),
            ])->all(),
            'listeningAutoStartSeconds' => config()->integer('glc.placement.listening.auto_start_seconds', 10),
        ]);
    }

    public function acknowledgeInstructions(Request $request): RedirectResponse
    {
        $attempt = $this->sessions->currentAttempt($request);

        if (! $attempt instanceof PlacementAttempt) {
            return redirect()->route('placement.entry');
        }

        if (($expected = $this->sessions->expectedRouteName($attempt)) !== 'placement.instructions') {
            return redirect()->route($expected);
        }

        $request->validate(['acknowledged' => ['accepted']]);

        $attempt->update(['instructions_acknowledged_at' => now()]);

        return redirect()->route('placement.device-check');
    }

    public function deviceCheck(Request $request): Response|RedirectResponse
    {
        $attempt = $this->sessions->currentAttempt($request);

        if (! $attempt instanceof PlacementAttempt) {
            return redirect()->route('placement.entry');
        }

        if (($expected = $this->sessions->expectedRouteName($attempt)) !== 'placement.device-check') {
            return redirect()->route($expected);
        }

        return Inertia::render('glc/placement/device-check', [
            'recordingMaxSeconds' => config()->integer('glc.placement.device_check.recording_max_seconds', 10),
        ]);
    }

    public function confirmDeviceCheck(Request $request): RedirectResponse
    {
        $attempt = $this->sessions->currentAttempt($request);

        if (! $attempt instanceof PlacementAttempt) {
            return redirect()->route('placement.entry');
        }

        if (($expected = $this->sessions->expectedRouteName($attempt)) !== 'placement.device-check') {
            return redirect()->route($expected);
        }

        $request->validate([
            'audio_ok' => ['accepted'],
            'microphone_ok' => ['accepted'],
            'recording_ok' => ['accepted'],
        ]);

        $this->timer->unlockFirstSection($attempt);

        return redirect()->route('placement.test');
    }
}
