<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Placement;

use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Models\Glc\PlacementAnswer;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementSectionState;
use App\Services\Glc\Placement\PlacementContentService;
use App\Services\Glc\Placement\PlacementSessionService;
use App\Services\Glc\Placement\SectionTimerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class SectionController
{
    public function __construct(
        private PlacementSessionService $sessions,
        private SectionTimerService $timer,
        private PlacementContentService $content,
    ) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $attempt = $this->sessions->currentAttempt($request);

        if (! $attempt instanceof PlacementAttempt) {
            return redirect()->route('placement.entry');
        }

        if (($expected = $this->sessions->expectedRouteName($attempt)) !== 'placement.test') {
            return redirect()->route($expected);
        }

        $sync = $this->timer->syncCurrentSection($attempt);

        if ($sync['finished']) {
            $this->sessions->finalizeSubmission($attempt);

            return redirect()->route('placement.complete');
        }

        $attempt->refresh();
        $section = $attempt->current_section;
        $state = $this->sessions->sectionState($attempt, $section);
        $attempt->update(['last_activity_at' => now()]);

        $common = [
            'candidateName' => $attempt->candidate_name,
            'progress' => $this->progressPayload($attempt, $section),
            'timer' => [
                'remainingSeconds' => $state->remainingSeconds(),
                'timeLimitSeconds' => $state->time_limit_seconds,
            ],
            'config' => [
                'heartbeatIntervalSeconds' => 20,
                'autosaveIntervalSeconds' => config()->integer('glc.placement.writing.autosave_interval_seconds', 5),
                'listeningAutoStartSeconds' => config()->integer('glc.placement.listening.auto_start_seconds', 10),
            ],
        ];

        return match ($section) {
            PlacementSection::Reading => Inertia::render('glc/placement/sections/reading', [
                ...$common,
                'passages' => $this->content->readingPayload(),
                'answers' => $this->content->savedSelections($attempt, $section),
            ]),
            PlacementSection::GrammarVocabulary => Inertia::render('glc/placement/sections/grammar-vocabulary', [
                ...$common,
                'questions' => $this->content->grammarVocabularyPayload(),
                'answers' => $this->content->savedSelections($attempt, $section),
            ]),
            PlacementSection::Listening => Inertia::render('glc/placement/sections/listening', [
                ...$common,
                'clips' => $this->content->listeningPayload($attempt),
                'answers' => $this->content->savedSelections($attempt, $section),
            ]),
            PlacementSection::Writing => Inertia::render('glc/placement/sections/writing', [
                ...$common,
                'prompt' => $this->content->writingPromptPayload(),
                'saved' => $this->savedWriting($attempt),
            ]),
            PlacementSection::Speaking => Inertia::render('glc/placement/sections/speaking', [
                ...$common,
                'prompt' => $this->content->speakingPromptPayload(),
                'recording' => $this->savedRecording($attempt),
            ]),
        };
    }

    public function complete(Request $request): RedirectResponse
    {
        $attempt = $this->sessions->currentAttempt($request);

        if (! $attempt instanceof PlacementAttempt) {
            return redirect()->route('placement.entry');
        }

        if (($expected = $this->sessions->expectedRouteName($attempt)) !== 'placement.test') {
            return redirect()->route($expected);
        }

        $validated = $request->validate([
            'section' => ['required', 'string', Rule::enum(PlacementSection::class)],
        ]);

        $section = PlacementSection::from($validated['section']);

        if ($attempt->current_section !== $section) {
            throw ValidationException::withMessages([
                'section' => 'Sections must be completed in order. You can only finish the section you are currently on.',
            ]);
        }

        if ($section === PlacementSection::Speaking) {
            throw ValidationException::withMessages([
                'section' => 'The Speaking section is finished by submitting your test.',
            ]);
        }

        $state = $this->sessions->sectionState($attempt, $section);

        if ($state->status !== PlacementSectionStatus::InProgress) {
            throw ValidationException::withMessages(['section' => 'This section is not in progress.']);
        }

        if ($section === PlacementSection::Writing) {
            $this->assertWritingMeetsMinimum($attempt);
        }

        $this->timer->completeSection($attempt, $state);

        return redirect()->route('placement.test');
    }

    private function assertWritingMeetsMinimum(PlacementAttempt $attempt): void
    {
        $prompt = $this->content->writingPromptPayload();
        $minWords = $prompt['minWords'] ?? config()->integer('glc.placement.writing.min_words', 150);
        $saved = $this->savedWriting($attempt);
        $wordCount = $saved['wordCount'] ?? 0;

        if ($wordCount < $minWords) {
            throw ValidationException::withMessages([
                'words' => "Your essay must be at least {$minWords} words. You currently have {$wordCount}.",
            ]);
        }
    }

    /**
     * @return array{text: string, wordCount: int}
     */
    private function savedWriting(PlacementAttempt $attempt): array
    {
        $answer = $this->answerForPrompt($attempt, PlacementSection::Writing);

        return [
            'text' => (string) ($answer->response['text'] ?? ''),
            'wordCount' => (int) ($answer->word_count ?? 0),
        ];
    }

    /**
     * @return array{attemptsUsed: int, hasRecording: bool, durationSeconds: int|null}
     */
    private function savedRecording(PlacementAttempt $attempt): array
    {
        $answer = $this->answerForPrompt($attempt, PlacementSection::Speaking);

        return [
            'attemptsUsed' => (int) ($answer->recording_attempts ?? 0),
            'hasRecording' => isset($answer->response['audio_path']),
            'durationSeconds' => $answer->response['duration_seconds'] ?? null,
        ];
    }

    private function answerForPrompt(PlacementAttempt $attempt, PlacementSection $section): PlacementAnswer
    {
        $prompt = $this->content->promptItem($section);

        $answer = $prompt instanceof PlacementItem
            ? $attempt->answers()->where('placement_item_id', $prompt->id)->first()
            : null;

        return $answer instanceof PlacementAnswer ? $answer : new PlacementAnswer;
    }

    /**
     * @return array<string, mixed>
     */
    private function progressPayload(PlacementAttempt $attempt, PlacementSection $current): array
    {
        $states = $attempt->sectionStates()->get()->keyBy(fn (PlacementSectionState $state): string => $state->section->value);

        return [
            'current' => $current->value,
            'currentLabel' => $current->label(),
            'currentIndex' => $current->order(),
            'total' => count(PlacementSection::ordered()),
            'sections' => collect(PlacementSection::ordered())->map(fn (PlacementSection $section): array => [
                'value' => $section->value,
                'label' => $section->label(),
                'order' => $section->order(),
                'status' => $states[$section->value]->status->value,
            ])->all(),
        ];
    }
}
