<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Staff;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Models\Glc\PlacementItem;
use App\Models\User;
use App\Services\Glc\AuditLogger;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PlacementContentController
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): Response
    {
        $items = PlacementItem::query()
            ->active()
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('position')
            ->get();

        $bySection = fn (PlacementSection $section) => $items
            ->where('section', $section)
            ->values()
            ->map(fn (PlacementItem $item): array => $this->presentItem($item));

        return Inertia::render('glc/staff/content-index', [
            'sections' => [
                'reading' => $bySection(PlacementSection::Reading),
                'grammar_vocabulary' => $bySection(PlacementSection::GrammarVocabulary),
                'listening' => $bySection(PlacementSection::Listening),
                'writing' => $bySection(PlacementSection::Writing),
                'speaking' => $bySection(PlacementSection::Speaking),
            ],
        ]);
    }

    public function store(Request $request, #[CurrentUser] User $user): RedirectResponse
    {
        $data = $this->validateItem($request);

        $section = PlacementSection::from($data['section']);
        $type = PlacementItemType::from($data['type']);

        if (in_array($type, [PlacementItemType::Prompt], true)) {
            $exists = PlacementItem::query()->active()->forSection($section)->where('type', $type)->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'body' => sprintf('An active %s prompt already exists. Edit it instead.', $section->label()),
                ]);
            }
        }

        $mediaPath = null;

        if ($type === PlacementItemType::AudioClip) {
            $audio = $request->file('audio');

            if (! $audio instanceof UploadedFile) {
                throw ValidationException::withMessages(['audio' => 'An MP3 or WAV file is required for listening clips.']);
            }

            $mediaPath = $audio->store('glc/placement/audio', 'local');
        }

        $item = PlacementItem::query()->create([
            'section' => $section,
            'type' => $type,
            'parent_id' => $data['parent_id'] ?? null,
            'position' => $data['position'] ?? $this->nextPosition($section, $data['parent_id'] ?? null),
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'options' => $data['options'] ?? null,
            'correct_option' => $data['correct_option'] ?? null,
            'media_path' => $mediaPath,
            'settings' => $data['settings'] ?? null,
            'is_active' => true,
        ]);

        $this->audit->log(AuditAction::PlacementContentChanged, $user, $item, [
            'event' => 'created',
            'section' => $section->value,
            'type' => $type->value,
        ]);

        return back()->with('success', 'Item created.');
    }

    public function update(Request $request, PlacementItem $item, #[CurrentUser] User $user): RedirectResponse
    {
        $data = $this->validateItem($request, $item);

        $attributes = [
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'options' => $data['options'] ?? null,
            'correct_option' => $data['correct_option'] ?? null,
            'settings' => $data['settings'] ?? null,
        ];

        if (array_key_exists('position', $data) && $data['position'] !== null) {
            $attributes['position'] = $data['position'];
        }

        $audio = $request->file('audio');

        if ($item->type === PlacementItemType::AudioClip && $audio instanceof UploadedFile) {
            $attributes['media_path'] = $audio->store('glc/placement/audio', 'local');
        }

        $item->update($attributes);

        $this->audit->log(AuditAction::PlacementContentChanged, $user, $item, [
            'event' => 'updated',
            'section' => $item->section->value,
            'type' => $item->type->value,
        ]);

        return back()->with('success', 'Item updated.');
    }

    public function destroy(PlacementItem $item, #[CurrentUser] User $user): RedirectResponse
    {
        $details = [
            'event' => 'deleted',
            'section' => $item->section->value,
            'type' => $item->type->value,
            'title' => $item->title,
        ];

        $item->delete();

        $this->audit->log(AuditAction::PlacementContentChanged, $user, null, $details);

        return back()->with('success', 'Item deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateItem(Request $request, ?PlacementItem $existing = null): array
    {
        $data = $request->validate([
            'section' => [$existing === null ? 'required' : 'sometimes', Rule::enum(PlacementSection::class)],
            'type' => [$existing === null ? 'required' : 'sometimes', Rule::enum(PlacementItemType::class)],
            'parent_id' => ['nullable', 'integer', Rule::exists('placement_items', 'id')],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:20000'],
            'options' => ['nullable', 'array', 'size:4'],
            'options.*' => ['required', 'string', 'max:500'],
            'correct_option' => ['nullable', 'integer', 'between:0,3'],
            'position' => ['nullable', 'integer', 'min:0'],
            'audio' => ['nullable', 'file', 'max:20480', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/wave'],
            'settings' => ['nullable', 'array'],
            'settings.min_words' => ['nullable', 'integer', 'min:1'],
            'settings.max_words' => ['nullable', 'integer', 'min:1'],
            'settings.max_duration_seconds' => ['nullable', 'integer', 'min:10'],
            'settings.max_attempts' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $data['section'] = $data['section'] ?? $existing?->section->value;
        $data['type'] = $data['type'] ?? $existing?->type->value;

        $this->validateSemantics($data, $existing);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateSemantics(array $data, ?PlacementItem $existing): void
    {
        $section = PlacementSection::from($data['section']);
        $type = PlacementItemType::from($data['type']);

        $fail = function (string $field, string $message): never {
            throw ValidationException::withMessages([$field => $message]);
        };

        match ($type) {
            PlacementItemType::Passage => $this->validatePassage($section, $data, $fail),
            PlacementItemType::Question => $this->validateQuestion($section, $data, $existing, $fail),
            PlacementItemType::AudioClip => $section === PlacementSection::Listening
                ? null
                : $fail('section', 'Audio clips belong to the Listening section.'),
            PlacementItemType::Prompt => in_array($section, [PlacementSection::Writing, PlacementSection::Speaking], true) && filled($data['body'] ?? null)
                ? null
                : $fail('body', 'Prompts need a body and belong to the Writing or Speaking section.'),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  callable(string, string): never  $fail
     */
    private function validatePassage(PlacementSection $section, array $data, callable $fail): void
    {
        if ($section !== PlacementSection::Reading) {
            $fail('section', 'Passages belong to the Reading section.');
        }

        if (blank($data['title'] ?? null) || blank($data['body'] ?? null)) {
            $fail('body', 'Passages require a title and a body.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  callable(string, string): never  $fail
     */
    private function validateQuestion(PlacementSection $section, array $data, ?PlacementItem $existing, callable $fail): void
    {
        if (blank($data['body'] ?? null) || ! is_array($data['options'] ?? null) || ! isset($data['correct_option'])) {
            $fail('options', 'Questions require a body, four options, and the correct option.');
        }

        $parentId = $data['parent_id'] ?? $existing?->parent_id;

        if ($section === PlacementSection::GrammarVocabulary) {
            if ($parentId !== null) {
                $fail('parent_id', 'Grammar/Vocabulary questions are standalone.');
            }

            return;
        }

        if (! in_array($section, [PlacementSection::Reading, PlacementSection::Listening], true)) {
            $fail('section', 'Questions belong to Reading, Grammar/Vocabulary, or Listening.');
        }

        $parent = $parentId === null ? null : PlacementItem::query()->find($parentId);

        if ($parent === null || $parent->section !== $section) {
            $fail('parent_id', 'Reading and Listening questions need a parent passage or clip in the same section.');
        }
    }

    private function nextPosition(PlacementSection $section, ?int $parentId): int
    {
        return (int) PlacementItem::query()
            ->forSection($section)
            ->where('parent_id', $parentId)
            ->max('position') + 1;
    }

    /**
     * @return array<string, mixed>
     */
    private function presentItem(PlacementItem $item): array
    {
        return [
            'id' => $item->id,
            'section' => $item->section->value,
            'type' => $item->type->value,
            'parent_id' => $item->parent_id,
            'position' => $item->position,
            'title' => $item->title,
            'body' => $item->body,
            'options' => $item->options,
            'correct_option' => $item->correct_option,
            'settings' => $item->settings,
            'audio_url' => $item->media_path !== null && Storage::disk('local')->exists($item->media_path)
                ? route('staff.items.audio', $item)
                : null,
            'children' => $item->children->where('is_active', true)->values()
                ->map(fn (PlacementItem $child): array => $this->presentItem($child))
                ->all(),
        ];
    }
}
