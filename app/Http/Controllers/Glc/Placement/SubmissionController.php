<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Placement;

use App\Enums\Glc\PlacementAttemptStatus;
use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Services\Glc\Placement\PlacementContentService;
use App\Services\Glc\Placement\PlacementSessionService;
use App\Services\Glc\Placement\SectionTimerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SubmissionController
{
    public function __construct(
        private PlacementSessionService $sessions,
        private SectionTimerService $timer,
        private PlacementContentService $content,
    ) {}

    public function submit(Request $request): RedirectResponse
    {
        $attempt = $this->sessions->currentAttempt($request);

        if (! $attempt instanceof PlacementAttempt) {
            return redirect()->route('placement.entry');
        }

        if (($expected = $this->sessions->expectedRouteName($attempt)) !== 'placement.test') {
            return redirect()->route($expected);
        }

        if ($attempt->current_section !== PlacementSection::Speaking) {
            throw ValidationException::withMessages([
                'submit' => 'Complete all sections in order before submitting.',
            ]);
        }

        $state = $this->sessions->sectionState($attempt, PlacementSection::Speaking);

        if ($state->status !== PlacementSectionStatus::InProgress) {
            throw ValidationException::withMessages(['submit' => 'The Speaking section is not in progress.']);
        }

        if (! $this->hasRecording($attempt)) {
            throw ValidationException::withMessages([
                'recording' => 'Record and save your speaking response before submitting the test.',
            ]);
        }

        $this->timer->completeSection($attempt, $state);
        $this->sessions->finalizeSubmission($attempt);

        return redirect()->route('placement.complete');
    }

    public function complete(Request $request): Response|RedirectResponse
    {
        $attempt = $this->sessions->currentAttempt($request);

        if (! $attempt instanceof PlacementAttempt) {
            return redirect()->route('placement.entry');
        }

        if ($attempt->status !== PlacementAttemptStatus::Submitted) {
            return redirect()->route($this->sessions->expectedRouteName($attempt));
        }

        return Inertia::render('glc/placement/complete', [
            'candidateName' => $attempt->candidate_name,
            'submittedAt' => $attempt->submitted_at?->toIso8601String(),
        ]);
    }

    private function hasRecording(PlacementAttempt $attempt): bool
    {
        $prompt = $this->content->promptItem(PlacementSection::Speaking);

        if (! $prompt instanceof PlacementItem) {
            return false;
        }

        $answer = $attempt->answers()->where('placement_item_id', $prompt->id)->first();

        return isset($answer?->response['audio_path']);
    }
}
