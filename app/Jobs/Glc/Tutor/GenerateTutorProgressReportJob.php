<?php

declare(strict_types=1);

namespace App\Jobs\Glc\Tutor;

use App\Models\Glc\TutorProgressReport;
use App\Services\Glc\Tutor\TutorProgressReportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class GenerateTutorProgressReportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $reportId) {}

    public function handle(TutorProgressReportService $reports): void
    {
        $report = TutorProgressReport::query()->find($this->reportId);

        if (! $report instanceof TutorProgressReport || $report->status !== 'pending') {
            return;
        }

        $reports->generate($report);
    }
}
