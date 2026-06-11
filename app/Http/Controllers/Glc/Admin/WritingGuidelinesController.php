<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Services\Glc\Admin\WritingEvaluationGuidelines;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class WritingGuidelinesController
{
    public function __construct(
        private WritingEvaluationGuidelines $guidelines,
        private AuditLogger $auditLogger,
    ) {}

    public function edit(Request $request): Response
    {
        return Inertia::render('glc/admin/settings/writing-guidelines', [
            'criteria' => $this->guidelines->effective(),
            'defaults' => $this->guidelines->defaults(),
            'isCustomized' => $this->guidelines->isCustomized(),
            'limits' => [
                'max_criteria' => WritingEvaluationGuidelines::MAX_CRITERIA,
                'max_title_length' => WritingEvaluationGuidelines::MAX_TITLE_LENGTH,
                'max_description_length' => WritingEvaluationGuidelines::MAX_DESCRIPTION_LENGTH,
            ],
            'status' => $request->session()->get('glc_status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var array{criteria: list<array{title: string, description: string}>} $validated */
        $validated = $request->validate([
            'criteria' => ['required', 'array', 'min:1', 'max:'.WritingEvaluationGuidelines::MAX_CRITERIA],
            'criteria.*.title' => ['required', 'string', 'max:'.WritingEvaluationGuidelines::MAX_TITLE_LENGTH],
            'criteria.*.description' => ['required', 'string', 'max:'.WritingEvaluationGuidelines::MAX_DESCRIPTION_LENGTH],
        ]);

        $criteria = array_values(array_map(fn (array $criterion): array => [
            'title' => $criterion['title'],
            'description' => $criterion['description'],
        ], $validated['criteria']));

        $this->guidelines->update($criteria);

        $this->auditLogger->log(AuditAction::SettingsUpdated, $request->user(), null, [
            'writing_guidelines' => [
                'action' => 'updated',
                'criteria_count' => count($criteria),
                'titles' => array_column($criteria, 'title'),
            ],
        ]);

        return to_route('admin.settings.writing-guidelines.edit')
            ->with('glc_status', 'Writing evaluation guidelines saved.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->guidelines->reset();

        $this->auditLogger->log(AuditAction::SettingsUpdated, $request->user(), null, [
            'writing_guidelines' => [
                'action' => 'reset_to_defaults',
                'criteria_count' => count($this->guidelines->defaults()),
                'titles' => array_column($this->guidelines->defaults(), 'title'),
            ],
        ]);

        return to_route('admin.settings.writing-guidelines.edit')
            ->with('glc_status', 'Writing evaluation guidelines reset to defaults.');
    }
}
