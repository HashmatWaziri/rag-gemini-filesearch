<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Placement;

use App\Enums\Glc\PlacementAccessCodeStatus;
use App\Enums\Glc\PlacementAttemptStatus;
use App\Models\Glc\PlacementAccessCode;
use App\Models\Glc\PlacementAttempt;
use App\Services\Glc\Placement\PlacementSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class EntryController
{
    public function __construct(private PlacementSessionService $sessions) {}

    public function show(Request $request): Response|RedirectResponse
    {
        $attempt = $this->sessions->currentAttempt($request);

        if ($attempt instanceof PlacementAttempt && $attempt->status === PlacementAttemptStatus::InProgress) {
            return redirect()->route($this->sessions->expectedRouteName($attempt));
        }

        return Inertia::render('glc/placement/entry', [
            'minimumAge' => config()->integer('glc.placement.minimum_age', 12),
        ]);
    }

    public function validateCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $code = $this->findCode($validated['code']);

        if (! $code instanceof PlacementAccessCode) {
            return response()->json(['message' => $this->unknownCodeMessage()], 422);
        }

        if ($code->status === PlacementAccessCodeStatus::InProgress) {
            $resolution = $this->resolveInProgressCode($request, $code);

            if ($resolution['resume'] instanceof PlacementAttempt) {
                return response()->json([
                    'valid' => true,
                    'resume' => true,
                    'redirect' => route($this->sessions->expectedRouteName($resolution['resume'])),
                ]);
            }

            if ($resolution['terminated']) {
                return response()->json([
                    'message' => 'This access code is already in use on another device, so the test was ended - please contact GLC.',
                    'redirect' => route('placement.terminated'),
                ], 409);
            }

            return response()->json(['message' => $this->codeErrorMessage($code)], 422);
        }

        if (! $code->isUsable()) {
            return response()->json(['message' => $this->codeErrorMessage($code)], 422);
        }

        return response()->json(['valid' => true]);
    }

    public function start(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'privacy_acknowledged' => ['accepted'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'age' => ['required', 'integer', 'between:3,120'],
        ]);

        $code = $this->findCode($validated['code']);

        if (! $code instanceof PlacementAccessCode) {
            throw ValidationException::withMessages(['code' => $this->unknownCodeMessage()]);
        }

        if ($code->status === PlacementAccessCodeStatus::InProgress) {
            $resolution = $this->resolveInProgressCode($request, $code);

            if ($resolution['resume'] instanceof PlacementAttempt) {
                return redirect()->route($this->sessions->expectedRouteName($resolution['resume']));
            }

            if ($resolution['terminated']) {
                return redirect()->route('placement.terminated');
            }

            throw ValidationException::withMessages(['code' => $this->codeErrorMessage($code)]);
        }

        if (! $code->isUsable()) {
            throw ValidationException::withMessages(['code' => $this->codeErrorMessage($code)]);
        }

        if ($validated['age'] < config()->integer('glc.placement.minimum_age', 12)) {
            return redirect()->route('placement.blocked');
        }

        $attempt = $this->sessions->start(
            $code,
            $validated['name'],
            $validated['email'],
            (int) $validated['age'],
        );

        return redirect()
            ->route('placement.instructions')
            ->withCookie($this->sessions->makeCookie($attempt));
    }

    private function findCode(string $input): ?PlacementAccessCode
    {
        return PlacementAccessCode::query()
            ->where('code', mb_strtoupper(mb_trim($input)))
            ->first();
    }

    /**
     * @return array{resume: PlacementAttempt|null, terminated: bool}
     */
    private function resolveInProgressCode(Request $request, PlacementAccessCode $code): array
    {
        $activeAttempt = $code->attempts()
            ->where('status', PlacementAttemptStatus::InProgress)
            ->latest('id')
            ->first();

        if (! $activeAttempt instanceof PlacementAttempt) {
            return ['resume' => null, 'terminated' => false];
        }

        $cookieAttempt = $this->sessions->currentAttempt($request);

        if ($cookieAttempt instanceof PlacementAttempt && $cookieAttempt->is($activeAttempt)) {
            return ['resume' => $activeAttempt, 'terminated' => false];
        }

        $this->sessions->terminateForDualDevice($activeAttempt);

        return ['resume' => null, 'terminated' => true];
    }

    private function codeErrorMessage(PlacementAccessCode $code): string
    {
        return match (true) {
            $code->status === PlacementAccessCodeStatus::Revoked => 'This access code has been revoked. Please contact GLC.',
            $code->status === PlacementAccessCodeStatus::Completed,
            $code->status === PlacementAccessCodeStatus::InProgress => 'This access code has already been used. Please contact GLC for a new code.',
            $code->isExpired() => 'This access code has expired. Please contact GLC for a new code.',
            default => $this->unknownCodeMessage(),
        };
    }

    private function unknownCodeMessage(): string
    {
        return 'That access code was not recognised. Check the code and try again.';
    }
}
