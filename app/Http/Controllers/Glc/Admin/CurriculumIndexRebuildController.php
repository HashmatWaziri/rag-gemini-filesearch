<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Services\Glc\AuditLogger;
use App\Services\Glc\Curriculum\GeminiFileSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class CurriculumIndexRebuildController
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function store(Request $request): RedirectResponse
    {
        if (! class_exists(GeminiFileSearchService::class)) {
            return to_route('admin.settings.edit')
                ->with('glc_status', "This tool isn't available yet.");
        }

        $service = app(GeminiFileSearchService::class);

        if (! $service->isConfigured()) {
            return to_route('admin.settings.edit')->with(
                'glc_status',
                "The AI service isn't set up on this environment yet — documents stay safe and can be re-published later.",
            );
        }

        $result = $service->rebuildStore();

        $this->auditLogger->log(AuditAction::CurriculumIndexRebuilt, $request->user(), null, [
            'total' => $result['total'],
            'succeeded' => $result['succeeded'],
            'failed' => $result['failed'],
        ]);

        return to_route('admin.settings.edit')->with('glc_status', $this->summary($result));
    }

    /**
     * @param  array{total: int, succeeded: int, failed: int}  $result
     */
    private function summary(array $result): string
    {
        $message = sprintf(
            'Re-published %d of %d documents to the AI Tutor.',
            $result['succeeded'],
            $result['total'],
        );

        if ($result['failed'] > 0) {
            $message .= sprintf(' %d failed — open Curriculum to retry.', $result['failed']);
        }

        return $message;
    }
}
