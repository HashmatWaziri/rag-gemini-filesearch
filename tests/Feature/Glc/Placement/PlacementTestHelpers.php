<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Models\Glc\PlacementAccessCode;
use App\Models\Glc\PlacementAttempt;
use App\Models\Glc\PlacementItem;
use App\Models\Glc\PlacementSectionState;
use App\Services\Glc\Placement\PlacementSessionService;

if (! function_exists('glcPlacementCookie')) {
    /**
     * @return array<string, string>
     */
    function glcPlacementCookie(PlacementAttempt $attempt): array
    {
        return [PlacementSessionService::COOKIE_NAME => $attempt->id.'|'.$attempt->device_token];
    }
}

if (! function_exists('glcStartedAttempt')) {
    /**
     * @param  array<string, mixed>  $attributes
     */
    function glcStartedAttempt(
        PlacementSection $current = PlacementSection::Reading,
        array $attributes = [],
    ): PlacementAttempt {
        $code = PlacementAccessCode::factory()->inProgress()->create();

        $attempt = PlacementAttempt::factory()
            ->for($code, 'accessCode')
            ->create(['current_section' => $current, ...$attributes]);

        foreach (PlacementSection::ordered() as $section) {
            $status = match (true) {
                $section->order() < $current->order() => PlacementSectionStatus::Completed,
                $section === $current => PlacementSectionStatus::InProgress,
                default => PlacementSectionStatus::Locked,
            };

            PlacementSectionState::factory()->forSection($section)->create([
                'placement_attempt_id' => $attempt->id,
                'status' => $status,
                'started_at' => $status === PlacementSectionStatus::Locked ? null : now(),
                'completed_at' => $status === PlacementSectionStatus::Completed ? now() : null,
                'last_resumed_at' => $status === PlacementSectionStatus::InProgress ? now() : null,
            ]);
        }

        return $attempt->refresh();
    }
}

if (! function_exists('glcOnboardingAttempt')) {
    /**
     * @param  array<string, mixed>  $attributes
     */
    function glcOnboardingAttempt(array $attributes = []): PlacementAttempt
    {
        $code = PlacementAccessCode::factory()->inProgress()->create();

        $attempt = PlacementAttempt::factory()
            ->for($code, 'accessCode')
            ->create([
                'instructions_acknowledged_at' => null,
                'current_section' => PlacementSection::Reading,
                ...$attributes,
            ]);

        foreach (PlacementSection::ordered() as $section) {
            PlacementSectionState::factory()->forSection($section)->create([
                'placement_attempt_id' => $attempt->id,
                'status' => PlacementSectionStatus::Locked,
            ]);
        }

        return $attempt->refresh();
    }
}

if (! function_exists('glcSeedReading')) {
    /**
     * @return array{passages: list<PlacementItem>, questions: list<PlacementItem>}
     */
    function glcSeedReading(int $passages = 2, int $questionsPerPassage = 6): array
    {
        $createdPassages = [];
        $createdQuestions = [];

        foreach (range(1, $passages) as $position) {
            $passage = PlacementItem::factory()->passage()->create(['position' => $position]);
            $createdPassages[] = $passage;

            foreach (range(1, $questionsPerPassage) as $questionPosition) {
                $createdQuestions[] = PlacementItem::factory()->create([
                    'section' => PlacementSection::Reading,
                    'parent_id' => $passage->id,
                    'position' => $questionPosition,
                ]);
            }
        }

        return ['passages' => $createdPassages, 'questions' => $createdQuestions];
    }
}

if (! function_exists('glcSeedGrammarVocabulary')) {
    /**
     * @return list<PlacementItem>
     */
    function glcSeedGrammarVocabulary(int $count = 4): array
    {
        $questions = [];

        foreach (range(1, $count) as $position) {
            $questions[] = PlacementItem::factory()
                ->forSection(PlacementSection::GrammarVocabulary)
                ->create(['position' => $position]);
        }

        return $questions;
    }
}

if (! function_exists('glcSeedListening')) {
    /**
     * @return array{clips: list<PlacementItem>, questions: list<PlacementItem>}
     */
    function glcSeedListening(int $clips = 2, int $questionsPerClip = 5): array
    {
        $createdClips = [];
        $createdQuestions = [];

        foreach (range(1, $clips) as $position) {
            $clip = PlacementItem::factory()->audioClip()->create([
                'position' => $position,
                'media_path' => "glc/placement/audio/clip-{$position}.mp3",
            ]);
            $createdClips[] = $clip;

            foreach (range(1, $questionsPerClip) as $questionPosition) {
                $createdQuestions[] = PlacementItem::factory()->create([
                    'section' => PlacementSection::Listening,
                    'parent_id' => $clip->id,
                    'position' => $questionPosition,
                ]);
            }
        }

        return ['clips' => $createdClips, 'questions' => $createdQuestions];
    }
}

if (! function_exists('glcSeedGapFillQuestion')) {
    /**
     * @param  list<string>  $acceptedAnswers
     */
    function glcSeedGapFillQuestion(
        PlacementItem $parent,
        array $acceptedAnswers = ['seven', '7'],
        int $position = 99,
    ): PlacementItem {
        return PlacementItem::factory()->create([
            'section' => $parent->section,
            'parent_id' => $parent->id,
            'position' => $position,
            'body' => 'The train leaves at _____ every morning.',
            'options' => null,
            'correct_option' => null,
            'settings' => ['format' => 'gap_fill', 'accepted_answers' => $acceptedAnswers],
        ]);
    }
}

if (! function_exists('glcSeedWritingPrompt')) {
    function glcSeedWritingPrompt(): PlacementItem
    {
        return PlacementItem::factory()->writingPrompt()->create(['position' => 1]);
    }
}

if (! function_exists('glcSeedSpeakingPrompt')) {
    function glcSeedSpeakingPrompt(): PlacementItem
    {
        return PlacementItem::factory()->speakingPrompt()->create(['position' => 1]);
    }
}

if (! function_exists('glcEssayText')) {
    function glcEssayText(int $words): string
    {
        return implode(' ', array_fill(0, $words, 'word'));
    }
}

if (! function_exists('glcAssertNoForbiddenKeys')) {
    /**
     * @param  array<array-key, mixed>  $payload
     */
    function glcAssertNoForbiddenKeys(array $payload, string $path = 'props'): void
    {
        $forbidden = [
            'correct_option', 'correctoption', 'is_correct',
            'accepted_answers', 'acceptedanswers',
            'score', 'scores', 'level', 'levels', 'final_level', 'skill_levels',
            'review', 'reviews', 'ai_draft', 'ai_drafts', 'aidrafts', 'draft', 'drafts',
            'media_path', 'confidence',
        ];

        foreach ($payload as $key => $value) {
            $normalized = mb_strtolower((string) $key);

            expect(in_array($normalized, $forbidden, true))->toBeFalse(
                "Forbidden key [{$key}] leaked to candidate payload at [{$path}].",
            );

            if (is_array($value)) {
                glcAssertNoForbiddenKeys($value, $path.'.'.$key);
            }
        }
    }
}
