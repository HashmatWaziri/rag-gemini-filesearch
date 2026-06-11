<?php

declare(strict_types=1);

namespace App\Jobs\Glc\Placement;

use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementAttempt;
use App\Services\Glc\Review\AiDraftService;
use App\Services\Glc\Review\ScoringService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class GeneratePlacementAiDraftsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $attemptId) {}

    public function handle(AiDraftService $drafts, ScoringService $scoring): void
    {
        $attempt = PlacementAttempt::query()->find($this->attemptId);

        if (! $attempt instanceof PlacementAttempt) {
            return;
        }

        foreach ([PlacementSection::Writing, PlacementSection::Speaking] as $section) {
            try {
                $drafts->generate($attempt, $section);
                $scoring->scoreAttempt($attempt);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
