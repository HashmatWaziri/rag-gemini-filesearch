<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Placement;

use App\Enums\Glc\PlacementItemType;
use App\Enums\Glc\PlacementSection;
use App\Enums\Glc\PlacementSectionStatus;
use App\Models\Glc\PlacementItem;
use App\Services\Glc\Placement\PlacementContentService;
use App\Services\Glc\Placement\PlacementSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class AnswerController
{
    public function __construct(
        private PlacementSessionService $sessions,
        private PlacementContentService $content,
    ) {}

    public function storeObjective(Request $request): JsonResponse
    {
        $attempt = $this->sessions->requireActiveAttempt($request);

        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
            'selected' => ['required', 'integer', 'min:0'],
        ]);

        $item = PlacementItem::query()->active()->find($validated['item_id']);

        if (! $item instanceof PlacementItem || $item->type !== PlacementItemType::Question) {
            return response()->json(['message' => 'Unknown question.'], 422);
        }

        $section = $attempt->current_section;

        if (! $section instanceof PlacementSection || $item->section !== $section) {
            return response()->json(['message' => 'This question is not part of your current section.'], 422);
        }

        $state = $this->sessions->sectionState($attempt, $section);

        if ($state->status !== PlacementSectionStatus::InProgress) {
            return response()->json(['message' => 'This section is not in progress.'], 409);
        }

        if ($validated['selected'] >= count($item->options ?? [])) {
            return response()->json(['message' => 'Invalid option for this question.'], 422);
        }

        $attempt->answers()->updateOrCreate(
            ['placement_item_id' => $item->id],
            ['response' => ['selected' => $validated['selected']]],
        );

        return response()->json([
            'saved' => true,
            'savedAt' => now()->toIso8601String(),
        ]);
    }

    public function storeWriting(Request $request): JsonResponse
    {
        $attempt = $this->sessions->requireActiveAttempt($request);

        $validated = $request->validate([
            'text' => ['present', 'string', 'max:50000'],
        ]);

        if ($attempt->current_section !== PlacementSection::Writing) {
            return response()->json(['message' => 'The Writing section is not your current section.'], 409);
        }

        $state = $this->sessions->sectionState($attempt, PlacementSection::Writing);

        if ($state->status !== PlacementSectionStatus::InProgress) {
            return response()->json(['message' => 'This section is not in progress.'], 409);
        }

        $prompt = $this->content->promptItem(PlacementSection::Writing);

        if (! $prompt instanceof PlacementItem) {
            return response()->json(['message' => 'No writing prompt is configured.'], 422);
        }

        $wordCount = PlacementContentService::countWords($validated['text']);

        $attempt->answers()->updateOrCreate(
            ['placement_item_id' => $prompt->id],
            [
                'response' => ['text' => $validated['text']],
                'word_count' => $wordCount,
            ],
        );

        return response()->json([
            'saved' => true,
            'wordCount' => $wordCount,
            'savedAt' => now()->toIso8601String(),
        ]);
    }
}
