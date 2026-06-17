<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementItem;
use Database\Seeders\GlcPlacementContentSeeder;
use Illuminate\Support\Facades\Storage;

it('seeds the full brief-aligned content pack', function (): void {
    Storage::fake('local');

    $this->seed(GlcPlacementContentSeeder::class);

    $count = fn (PlacementSection $section, PlacementItemType $type): int => PlacementItem::query()
        ->active()
        ->where('section', $section)
        ->where('type', $type)
        ->count();

    expect($count(PlacementSection::Reading, PlacementItemType::Passage))->toBe(2)
        ->and($count(PlacementSection::Reading, PlacementItemType::Question))->toBe(12)
        ->and($count(PlacementSection::GrammarVocabulary, PlacementItemType::Question))->toBe(22)
        ->and($count(PlacementSection::Listening, PlacementItemType::AudioClip))->toBe(2)
        ->and($count(PlacementSection::Listening, PlacementItemType::Question))->toBe(12)
        ->and($count(PlacementSection::Writing, PlacementItemType::Prompt))->toBe(1)
        ->and($count(PlacementSection::Speaking, PlacementItemType::Prompt))->toBe(1);
});

it('copies the bundled British Council MP3 clips into placement audio storage', function (): void {
    Storage::fake('local');

    $this->seed(GlcPlacementContentSeeder::class);

    $clips = PlacementItem::query()
        ->where('type', PlacementItemType::AudioClip)
        ->orderBy('position')
        ->get();

    expect($clips->pluck('title')->all())->toBe([
        'Announcements: Trains and flights',
        'Conversation: Meeting an old friend',
    ])->and($clips->pluck('media_path')->all())->toBe([
        'glc/placement/audio/listening-transport-announcements.mp3',
        'glc/placement/audio/listening-meeting-old-friend.mp3',
    ]);

    $clips->each(function (PlacementItem $clip): void {
        Storage::disk('local')->assertExists($clip->media_path);
        expect((string) Storage::disk('local')->get($clip->media_path))->toStartWith('ID3');
    });
});

it('seeds listening questions in mixed IELTS-style formats', function (): void {
    Storage::fake('local');

    $this->seed(GlcPlacementContentSeeder::class);

    $listening = PlacementItem::query()
        ->where('section', PlacementSection::Listening)
        ->where('type', PlacementItemType::Question)
        ->get();

    $gapFills = $listening->filter(fn (PlacementItem $q): bool => ($q->settings['format'] ?? null) === 'gap_fill');
    $trueFalse = $listening->filter(fn (PlacementItem $q): bool => $q->options === ['True', 'False']);

    expect($gapFills)->toHaveCount(4)
        ->and($trueFalse)->toHaveCount(2);

    $gapFills->each(function (PlacementItem $question): void {
        expect($question->body)->toContain('_____')
            ->and($question->options)->toBeNull()
            ->and($question->correct_option)->toBeNull()
            ->and($question->settings['accepted_answers'])->not->toBeEmpty();
    });

    $platformQuestion = $gapFills->firstOrFail(fn (PlacementItem $q): bool => str_contains((string) $q->body, 'Platform'));
    expect($platformQuestion->settings['accepted_answers'])->toBe(['9', 'nine', 'platform 9']);
});

it('creates well-formed items and prompt settings', function (): void {
    Storage::fake('local');

    $this->seed(GlcPlacementContentSeeder::class);

    PlacementItem::query()
        ->where('type', PlacementItemType::Question)
        ->get()
        ->each(function (PlacementItem $question): void {
            if (($question->settings['format'] ?? null) === 'gap_fill') {
                expect($question->options)->toBeNull()
                    ->and($question->correct_option)->toBeNull();
            } else {
                expect(count($question->options))->toBeGreaterThanOrEqual(2)
                    ->and(count($question->options))->toBeLessThanOrEqual(4)
                    ->and($question->correct_option)->toBeGreaterThanOrEqual(0)
                    ->and($question->correct_option)->toBeLessThan(count($question->options));
            }

            expect($question->parent_id)->when(
                $question->section !== PlacementSection::GrammarVocabulary,
                fn ($value) => $value->not->toBeNull(),
            );
        });

    $writing = PlacementItem::query()->where('section', PlacementSection::Writing)->firstOrFail();
    $speaking = PlacementItem::query()->where('section', PlacementSection::Speaking)->firstOrFail();

    expect($writing->settings['min_words'])->toBe(150)
        ->and($writing->settings['max_words'])->toBe(250)
        ->and($speaking->settings['max_duration_seconds'])->toBe(180)
        ->and($speaking->settings['max_attempts'])->toBe(3);
});

it('is idempotent and skips when active items already exist', function (): void {
    Storage::fake('local');

    $this->seed(GlcPlacementContentSeeder::class);
    $countAfterFirstRun = PlacementItem::query()->count();

    $this->seed(GlcPlacementContentSeeder::class);

    expect(PlacementItem::query()->count())->toBe($countAfterFirstRun);
});
