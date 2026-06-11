<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\PlacementAccessCodeStatus;
use App\Models\Glc\PlacementAccessCode;
use App\Services\Glc\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AccessCodeController
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request): Response
    {
        $status = PlacementAccessCodeStatus::tryFrom((string) $request->query('status'));
        $search = mb_strtoupper(mb_trim((string) $request->query('search')));

        $codes = PlacementAccessCode::query()
            ->with('issuer:id,name')
            ->withCount('attempts')
            ->when($status instanceof PlacementAccessCodeStatus, fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where('code', 'like', "%{$search}%"))
            ->latest()
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PlacementAccessCode $code): array => [
                'id' => $code->id,
                'code' => $code->code,
                'status' => $code->status->value,
                'status_label' => $code->status->label(),
                'is_expired' => $code->isExpired(),
                'expires_at' => $code->expires_at?->toIso8601String(),
                'revoked_at' => $code->revoked_at?->toIso8601String(),
                'note' => $code->note,
                'issuer_name' => $code->issuer?->name,
                'attempts_count' => $code->attempts_count,
                'can_revoke' => $this->canRevoke($code),
                'created_at' => $code->created_at->toIso8601String(),
            ]);

        return Inertia::render('glc/admin/access-codes/index', [
            'codes' => $codes,
            'filters' => ['status' => $status?->value, 'search' => $search],
            'statuses' => array_map(fn (PlacementAccessCodeStatus $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ], PlacementAccessCodeStatus::cases()),
            'status' => $request->session()->get('glc_status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'between:1,100'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $quantity = (int) $validated['quantity'];

        for ($i = 0; $i < $quantity; $i++) {
            $code = PlacementAccessCode::query()->create([
                'code' => $this->uniqueCode(),
                'status' => PlacementAccessCodeStatus::Unused,
                'expires_at' => $validated['expires_at'] ?? null,
                'note' => $validated['note'] ?? null,
                'issued_by' => $request->user()?->id,
            ]);

            $this->auditLogger->log(AuditAction::AccessCodeCreated, $request->user(), $code, [
                'code' => $code->code,
                'expires_at' => $code->expires_at?->toIso8601String(),
            ]);
        }

        return to_route('admin.access-codes.index')->with(
            'glc_status',
            $quantity === 1 ? 'Access code created.' : "{$quantity} access codes created.",
        );
    }

    public function revoke(Request $request, PlacementAccessCode $accessCode): RedirectResponse
    {
        if (! $this->canRevoke($accessCode)) {
            throw ValidationException::withMessages([
                'code' => 'Only unused or in-progress codes can be revoked.',
            ]);
        }

        $accessCode->update([
            'status' => PlacementAccessCodeStatus::Revoked,
            'revoked_at' => now(),
        ]);

        $this->auditLogger->log(AuditAction::AccessCodeRevoked, $request->user(), $accessCode, [
            'code' => $accessCode->code,
        ]);

        return back()->with('glc_status', "Access code {$accessCode->code} revoked.");
    }

    private function canRevoke(PlacementAccessCode $code): bool
    {
        return in_array($code->status, [
            PlacementAccessCodeStatus::Unused,
            PlacementAccessCodeStatus::InProgress,
        ], true);
    }

    private function uniqueCode(): string
    {
        do {
            $code = PlacementAccessCode::generateCode();
        } while (PlacementAccessCode::query()->where('code', $code)->exists());

        return $code;
    }
}
