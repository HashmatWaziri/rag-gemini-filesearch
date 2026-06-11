<?php

declare(strict_types=1);

namespace App\Services\Glc\Placement;

use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementSectionState;
use Carbon\CarbonInterface;
use LogicException;

final class SectionTimerService
{
    public function __construct(private readonly PlacementSessionService $sessions) {}

    public function recordElapsed(PlacementSectionState $state): PlacementSectionState
    {
        if ($state->status !== PlacementSectionStatus::InProgress) {
            return $state;
        }

        $anchor = $state->last_resumed_at;
        $elapsed = $anchor instanceof CarbonInterface ? (int) $anchor->diffInSeconds(now()) : 0;
        $inactivityThreshold = config()->integer('glc.placement.inactivity_pause_seconds', 1800);

        $accumulated = ($elapsed > 0 && $elapsed <= $inactivityThreshold) ? $elapsed : 0;

        $state->update([
            'time_used_seconds' => min($state->time_limit_seconds, $state->time_used_seconds + $accumulated),
            'last_resumed_at' => now(),
            'paused_at' => $elapsed > $inactivityThreshold ? $anchor : null,
        ]);

        return $state->refresh();
    }

    public function unlockFirstSection(PlacementAttempt $attempt): void
    {
        $state = $this->sessions->sectionState($attempt, PlacementSection::Reading);

        if ($state->status !== PlacementSectionStatus::Locked) {
            return;
        }

        $state->update([
            'status' => PlacementSectionStatus::InProgress,
            'started_at' => now(),
            'last_resumed_at' => now(),
        ]);

        $attempt->update(['current_section' => PlacementSection::Reading]);
    }

    public function completeSection(PlacementAttempt $attempt, PlacementSectionState $state): ?PlacementSection
    {
        $this->recordElapsed($state);

        $state->update([
            'status' => PlacementSectionStatus::Completed,
            'completed_at' => now(),
        ]);

        $next = $state->section->next();

        if (! $next instanceof PlacementSection) {
            $attempt->update(['current_section' => null]);

            return null;
        }

        $nextState = $this->sessions->sectionState($attempt, $next);
        $nextState->update([
            'status' => PlacementSectionStatus::InProgress,
            'started_at' => now(),
            'last_resumed_at' => now(),
        ]);

        $attempt->update(['current_section' => $next]);

        return $next;
    }

    /**
     * @return array{state: PlacementSectionState, advanced: bool, finished: bool}
     */
    public function syncCurrentSection(PlacementAttempt $attempt): array
    {
        $section = $attempt->current_section;

        if (! $section instanceof PlacementSection) {
            throw new LogicException('Attempt has no current section to sync.');
        }

        $state = $this->sessions->sectionState($attempt, $section);
        $state = $this->recordElapsed($state);

        if (! $state->isTimeExpired()) {
            return ['state' => $state, 'advanced' => false, 'finished' => false];
        }

        $next = $this->completeSection($attempt, $state);

        return [
            'state' => $state->refresh(),
            'advanced' => $next instanceof PlacementSection,
            'finished' => $next === null,
        ];
    }
}
