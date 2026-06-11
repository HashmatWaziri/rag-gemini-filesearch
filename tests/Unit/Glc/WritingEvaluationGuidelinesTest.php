<?php

declare(strict_types=1);

use App\Enums\SettingKey;
use App\Models\Setting;
use App\Services\Glc\Admin\WritingEvaluationGuidelines;

beforeEach(function (): void {
    $this->guidelines = app(WritingEvaluationGuidelines::class);
});

it('returns config defaults when nothing is stored', function (): void {
    expect($this->guidelines->isCustomized())->toBeFalse()
        ->and($this->guidelines->effective())->toBe($this->guidelines->defaults())
        ->and($this->guidelines->effective())->not->toBeEmpty();
});

it('persists custom criteria and reports customization', function (): void {
    $criteria = [
        ['title' => 'Spelling', 'description' => 'Accurate spelling throughout.'],
        ['title' => 'Tone', 'description' => 'Appropriate formal register.'],
    ];

    $this->guidelines->update($criteria);

    expect($this->guidelines->isCustomized())->toBeTrue()
        ->and($this->guidelines->effective())->toBe($criteria);
});

it('falls back to defaults when stored value is malformed', function (): void {
    Setting::set(SettingKey::GlcWritingGuidelines, 'not-json');

    expect($this->guidelines->effective())->toBe($this->guidelines->defaults());
});

it('resets to defaults', function (): void {
    $this->guidelines->update([['title' => 'Spelling', 'description' => 'Accurate spelling.']]);
    $this->guidelines->reset();

    expect($this->guidelines->isCustomized())->toBeFalse()
        ->and($this->guidelines->effective())->toBe($this->guidelines->defaults());
});

it('renders a numbered prompt block', function (): void {
    $this->guidelines->update([
        ['title' => 'Spelling', 'description' => 'Accurate spelling.'],
        ['title' => 'Tone', 'description' => 'Formal register.'],
    ]);

    expect($this->guidelines->asPromptBlock())
        ->toBe("1. Spelling: Accurate spelling.\n2. Tone: Formal register.");
});
