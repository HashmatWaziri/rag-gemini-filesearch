<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use App\Enums\Glc\AuditAction;
use App\Services\Glc\Admin\SkillEvaluationGuidelines;
use App\Services\Glc\Admin\SpeakingEvaluationGuidelines;
use App\Services\Glc\Admin\WritingEvaluationGuidelines;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class PlacementCriteriaController
{
    public function __construct(
        private WritingEvaluationGuidelines $writingGuidelines,
        private SpeakingEvaluationGuidelines $speakingGuidelines,
        private AuditLogger $auditLogger,
    ) {}

    public function update(Request $request, string $skill): RedirectResponse
    {
        $guidelines = $this->guidelinesFor($skill);

        /** @var array{criteria: list<array{title: string, description: string}>} $validated */
        $validated = $request->validate([
            'criteria' => ['required', 'array', 'min:1', 'max:'.SkillEvaluationGuidelines::MAX_CRITERIA],
            'criteria.*.title' => ['required', 'string', 'max:'.SkillEvaluationGuidelines::MAX_TITLE_LENGTH],
            'criteria.*.description' => ['required', 'string', 'max:'.SkillEvaluationGuidelines::MAX_DESCRIPTION_LENGTH],
        ]);

        $criteria = array_values(array_map(fn (array $criterion): array => [
            'title' => $criterion['title'],
            'description' => $criterion['description'],
        ], $validated['criteria']));

        $guidelines->update($criteria);

        $this->auditLogger->log(AuditAction::SettingsUpdated, $request->user(), null, [
            $skill.'_guidelines' => [
                'action' => 'updated',
                'criteria_count' => count($criteria),
                'titles' => array_column($criteria, 'title'),
            ],
        ]);

        return back()->with('success', ucfirst($skill).' marking criteria saved.');
    }

    public function destroy(Request $request, string $skill): RedirectResponse
    {
        $guidelines = $this->guidelinesFor($skill);

        $guidelines->reset();

        $this->auditLogger->log(AuditAction::SettingsUpdated, $request->user(), null, [
            $skill.'_guidelines' => [
                'action' => 'reset_to_defaults',
                'criteria_count' => count($guidelines->defaults()),
                'titles' => array_column($guidelines->defaults(), 'title'),
            ],
        ]);

        return back()->with('success', ucfirst($skill).' marking criteria reset to the GLC defaults.');
    }

    private function guidelinesFor(string $skill): SkillEvaluationGuidelines
    {
        return match ($skill) {
            'writing' => $this->writingGuidelines,
            'speaking' => $this->speakingGuidelines,
            default => abort(404),
        };
    }
}
