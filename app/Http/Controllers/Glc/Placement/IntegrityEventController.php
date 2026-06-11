<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Placement;

use App\Enums\Glc\PlacementIntegrityEventType;
use App\Services\Glc\Placement\PlacementSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final readonly class IntegrityEventController
{
    public function __construct(private PlacementSessionService $sessions) {}

    public function store(Request $request): JsonResponse
    {
        $attempt = $this->sessions->requireActiveAttempt($request);

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in([
                PlacementIntegrityEventType::TabSwitch->value,
                PlacementIntegrityEventType::PasteAttempt->value,
            ])],
            'context' => ['nullable', 'string', 'max:255'],
        ]);

        $attempt->integrityEvents()->create([
            'type' => PlacementIntegrityEventType::from($validated['type']),
            'metadata' => filled($validated['context'] ?? null) ? ['context' => $validated['context']] : null,
            'occurred_at' => now(),
        ]);

        return response()->json(['recorded' => true]);
    }
}
