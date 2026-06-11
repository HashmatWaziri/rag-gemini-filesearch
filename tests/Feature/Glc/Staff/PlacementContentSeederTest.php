<?php

declare(strict_types=1);

use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementItem;
use Database\Seeders\GlcPlacementContentSeeder;
use Illuminate\Support\Facades\Storage;

it('seeds the full brief-aligned placeholder pack', function (): void {
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
        ->and($count(PlacementSection::Listening, PlacementItemType::Question))->toBe(10)
        ->and($count(PlacementSection::Writing, PlacementItemType::Prompt))->toBe(1)
        ->and($count(PlacementSection::Speaking, PlacementItemType::Prompt))->toBe(1);
});

it('creates playable placeholder audio files and well-formed items', function (): void {
    Storage::fake('local');

    $this->seed(GlcPlacementContentSeeder::class);

    PlacementItem::query()
        ->where('type', PlacementItemType::AudioClip)
        ->get()
        ->each(function (PlacementItem $clip): void {
            Storage::disk('local')->assertExists($clip->media_path);
            expect((string) Storage::disk('local')->get($clip->media_path))->toStartWith('RIFF');
        });

    PlacementItem::query()
        ->where('type', PlacementItemType::Question)
        ->get()
        ->each(function (PlacementItem $question): void {
            expect($question->options)->toHaveCount(4)
                ->and($question->correct_option)->toBeGreaterThanOrEqual(0)
                ->and($question->correct_option)->toBeLessThanOrEqual(3)
                ->and($question->parent_id)->when(
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
