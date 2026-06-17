<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use App\Enums\Glc\GlcLevel;
use App\Enums\Glc\PlacementAiDraftStatus;
use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\UserRole;
use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementResultLink;
use App\Models\Glc\PlacementReview;
use App\Models\Glc\PlacementReviewNote;
use App\Models\User;
use App\Services\Glc\Admin\PlacementScoringSettings;
use App\Services\Glc\Admin\SpeakingEvaluationGuidelines;
use App\Services\Glc\Admin\WritingEvaluationGuidelines;
use App\Services\Glc\Ai\PlacementAiSettings;
use App\Services\Glc\Review\ObjectiveContextBuilder;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ReviewController
{
    public function show(
        PlacementReview $review,
        #[CurrentUser] User $user,
        ObjectiveContextBuilder $objectiveContext,
        WritingEvaluationGuidelines $guidelines,
        SpeakingEvaluationGuidelines $speakingGuidelines,
        PlacementAiSettings $aiSettings,
        PlacementScoringSettings $scoringSettings,
    ): Response {
        $this->authorizeAccess($review, $user);

        $attempt = $review->attempt->load(['score', 'aiDrafts', 'aiRecommendation', 'integrityEvents', 'answers.item', 'resultLinks.sender']);

        if ($attempt->integrityEvents->isNotEmpty() && ! $review->hasFlag('integrity')) {
            $review->update(['flags' => [...($review->flags ?? []), 'integrity']]);
        }

        $review->load(['assignee', 'approver', 'notes.author']);

        $answersByItem = $attempt->answers->keyBy('placement_item_id');
        $score = $attempt->score;

        $recommendation = $attempt->aiRecommendation;
        $recommendationCompleted = $recommendation?->status === PlacementAiDraftStatus::Completed;

        $suggestedSkillLevels = [];

        foreach (PlacementSection::ordered() as $section) {
            $aiLevel = $recommendationCompleted ? ($recommendation->skill_levels[$section->value] ?? null) : null;

            if (is_string($aiLevel) && GlcLevel::tryFrom($aiLevel) !== null) {
                $suggestedSkillLevels[$section->value] = $aiLevel;

                continue;
            }

            $pct = $score?->section_scores[$section->value] ?? null;
            $suggestedSkillLevels[$section->value] = is_numeric($pct) ? $scoringSettings->levelFromComposite((float) $pct)->value : null;
        }

        $supervises = $user->role !== UserRole::Teacher;

        return Inertia::render('glc/staff/review-show', [
            'review' => [
                'id' => $review->id,
                'status' => $review->status->value,
                'status_label' => $review->status->label(),
                'flags' => $review->flags ?? [],
                'assigned_to' => $review->assigned_to,
                'assignee' => $review->assignee?->name,
                'is_assigned_to_me' => $review->assigned_to === $user->id,
                'final_level' => $review->final_level?->value,
                'skill_levels' => $review->skill_levels,
                'override_reason' => $review->override_reason,
                'narrative' => $review->narrative,
                'narrative_approved_at' => $review->narrative_approved_at?->toDateTimeString(),
                'approved_at' => $review->approved_at?->toDateTimeString(),
                'approved_by' => $review->approver?->name,
                'can_generate_pdf' => $review->canGeneratePdf(),
            ],
            'candidate' => [
                'name' => $attempt->candidate_name,
                'email' => $attempt->candidate_email,
                'age' => $attempt->candidate_age,
                'is_minor' => $attempt->isMinor(),
            ],
            'attempt' => [
                'id' => $attempt->id,
                'started_at' => $attempt->started_at?->toDateTimeString(),
                'submitted_at' => $attempt->submitted_at?->toDateTimeString(),
            ],
            'score' => $score === null ? null : [
                'section_scores' => $score->section_scores,
                'composite' => $score->composite,
                'suggested_level' => $score->suggested_level?->value,
                'suggested_level_label' => $score->suggested_level?->label(),
                'variance_flagged' => $score->variance_flagged,
                'computed_at' => $score->computed_at?->toDateTimeString(),
            ],
            'suggested_skill_levels' => $suggestedSkillLevels,
            'ai_recommendation' => $recommendation === null ? null : [
                'status' => $recommendation->status->value,
                'recommended_level' => $recommendation->recommended_level?->value,
                'recommended_level_label' => $recommendation->recommended_level?->label(),
                'skill_levels' => $recommendation->skill_levels,
                'skill_summaries' => $recommendation->skill_summaries,
                'confidence' => $recommendation->confidence,
                'rationale' => $recommendation->rationale,
                'error' => $recommendation->error,
                'generated_at' => $recommendation->generated_at?->toDateTimeString(),
            ],
            'objective_breakdown' => $objectiveContext->summary($attempt),
            'writing_guidelines' => [
                'titles' => array_column($guidelines->effective(), 'title'),
                'customized' => $guidelines->isCustomized(),
            ],
            'speaking_guidelines' => [
                'titles' => array_column($speakingGuidelines->effective(), 'title'),
                'customized' => $speakingGuidelines->isCustomized(),
            ],
            'ai_models' => [
                'writing' => $this->aiModelAttribution($aiSettings, PlacementAiSettings::TASK_WRITING),
                'speaking_evaluation' => $this->aiModelAttribution($aiSettings, PlacementAiSettings::TASK_SPEAKING_EVALUATION),
                'speaking' => $this->aiModelAttribution($aiSettings, PlacementAiSettings::TASK_SPEAKING),
            ],
            'sections' => $this->sectionPayload($review, $answersByItem),
            'ai_drafts' => $attempt->aiDrafts->mapWithKeys(fn ($draft): array => [
                $draft->section->value => [
                    'status' => $draft->status->value,
                    'dimension_scores' => $draft->dimension_scores,
                    'feedback' => $draft->feedback,
                    'transcript' => $draft->transcript,
                    'confidence' => $draft->confidence,
                    'error' => $draft->error,
                    'generated_at' => $draft->generated_at?->toDateTimeString(),
                ],
            ]),
            'integrity_events' => $attempt->integrityEvents->map(fn ($event): array => [
                'type' => $event->type->value,
                'label' => $event->type->label(),
                'occurred_at' => $event->occurred_at->toDateTimeString(),
            ]),
            'notes' => $review->notes->sortByDesc('created_at')->values()->map(fn (PlacementReviewNote $note): array => [
                'id' => $note->id,
                'author' => $note->author?->name ?? 'Staff',
                'note' => $note->note,
                'created_at' => $note->created_at?->toDateTimeString(),
            ]),
            'result_links' => $attempt->resultLinks->sortByDesc('sent_at')->values()->map(fn (PlacementResultLink $link): array => [
                'id' => $link->id,
                'email_to' => $link->email_to,
                'sent_at' => $link->sent_at?->toDateTimeString(),
                'sent_by' => $link->sender?->name,
                'expires_at' => $link->expires_at->toDateTimeString(),
                'last_viewed_at' => $link->last_viewed_at?->toDateTimeString(),
                'expired' => $link->isExpired(),
            ]),
            'levels' => collect(GlcLevel::cases())->map(fn (GlcLevel $level): array => [
                'value' => $level->value,
                'label' => $level->label(),
            ]),
            'staff' => $supervises
                ? User::query()->whereIn('role', UserRole::staffValues())->orderBy('name')->get(['id', 'name'])
                    ->map(fn (User $member): array => ['id' => $member->id, 'name' => $member->name])
                : [],
            'supervises' => $supervises,
        ]);
    }

    /**
     * @return array{provider: string, model: string}
     */
    private function aiModelAttribution(PlacementAiSettings $aiSettings, string $task): array
    {
        $selection = $aiSettings->selection($task);
        $catalog = $aiSettings->catalog($task);
        $providerEntry = $catalog[$selection['provider']] ?? null;
        $modelEntry = $providerEntry['models'][$selection['model']] ?? null;

        return [
            'provider' => is_string($providerEntry['label'] ?? null) ? $providerEntry['label'] : $selection['provider'],
            'model' => is_array($modelEntry) && is_string($modelEntry['label'] ?? null) ? $modelEntry['label'] : $selection['model'],
        ];
    }

    private function authorizeAccess(PlacementReview $review, User $user): void
    {
        if ($user->role === UserRole::Teacher) {
            abort_unless($review->assigned_to === null || $review->assigned_to === $user->id, 403);
        }
    }

    /**
     * @param  Collection<int|string, PlacementAnswer>  $answersByItem
     * @return array<string, mixed>
     */
    private function sectionPayload(PlacementReview $review, Collection $answersByItem): array
    {
        $items = PlacementItem::query()
            ->active()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('position')
            ->get();

        $question = function (PlacementItem $item) use ($answersByItem): array {
            $answer = $answersByItem->get($item->id);
            $selected = $answer?->response['selected'] ?? null;
            $isGapFill = ($item->settings['format'] ?? null) === 'gap_fill';
            $text = $answer?->response['text'] ?? null;

            return [
                'id' => $item->id,
                'body' => $item->body,
                'format' => $isGapFill ? 'gap_fill' : 'mcq',
                'options' => $item->options,
                'correct_option' => $item->correct_option,
                'selected' => is_numeric($selected) ? (int) $selected : null,
                'answer_text' => $isGapFill && is_string($text) ? $text : null,
                'accepted_answers' => $isGapFill ? ($item->settings['accepted_answers'] ?? []) : null,
                'is_correct' => $answer?->is_correct,
            ];
        };

        $children = fn (PlacementItem $parent): array => $parent->children
            ->where('is_active', true)
            ->values()
            ->map($question)
            ->all();

        $writingPrompt = $items->first(fn (PlacementItem $item): bool => $item->section === PlacementSection::Writing && $item->type === PlacementItemType::Prompt);
        $speakingPrompt = $items->first(fn (PlacementItem $item): bool => $item->section === PlacementSection::Speaking && $item->type === PlacementItemType::Prompt);

        $writingAnswer = $writingPrompt === null ? null : $answersByItem->get($writingPrompt->id);
        $speakingAnswer = $speakingPrompt === null ? null : $answersByItem->get($speakingPrompt->id);

        $recordingPath = $speakingAnswer?->response['audio_path'] ?? null;

        return [
            'reading' => $items
                ->filter(fn (PlacementItem $item): bool => $item->section === PlacementSection::Reading && $item->type === PlacementItemType::Passage)
                ->values()
                ->map(fn (PlacementItem $passage): array => [
                    'id' => $passage->id,
                    'title' => $passage->title,
                    'body' => $passage->body,
                    'questions' => $children($passage),
                ]),
            'grammar_vocabulary' => $items
                ->filter(fn (PlacementItem $item): bool => $item->section === PlacementSection::GrammarVocabulary && $item->type === PlacementItemType::Question)
                ->values()
                ->map($question),
            'listening' => $items
                ->filter(fn (PlacementItem $item): bool => $item->section === PlacementSection::Listening && $item->type === PlacementItemType::AudioClip)
                ->values()
                ->map(fn (PlacementItem $clip): array => [
                    'id' => $clip->id,
                    'title' => $clip->title,
                    'audio_url' => $clip->media_path !== null ? route('staff.items.audio', $clip) : null,
                    'questions' => $children($clip),
                ]),
            'writing' => [
                'prompt' => $writingPrompt?->body,
                'essay' => $writingAnswer?->response['text'] ?? null,
                'word_count' => $writingAnswer?->word_count,
            ],
            'speaking' => [
                'prompt' => $speakingPrompt?->body,
                'recording_url' => is_string($recordingPath) && Storage::disk('local')->exists($recordingPath)
                    ? route('staff.review.recording', $review)
                    : null,
                'duration_seconds' => $speakingAnswer?->response['duration_seconds'] ?? null,
                'recording_attempts' => $speakingAnswer?->recording_attempts,
            ],
        ];
    }
}
