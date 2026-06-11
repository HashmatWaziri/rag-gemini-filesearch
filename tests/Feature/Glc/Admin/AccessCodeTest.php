<?php

declare(strict_types=1);

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\PlacementAccessCodeStatus;
use App\Models\Glc\AuditLog;
use App\Models\Glc\PlacementAccessCode;
use App\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
});

it('redirects guests and blocks non-admin roles', function (): void {
    $this->get(route('admin.access-codes.index'))->assertRedirectToRoute('login');

    $supervisor = User::factory()->academicSupervisor()->create();

    $this->actingAs($supervisor)->get(route('admin.access-codes.index'))->assertForbidden();
});

it('lists codes with status, expiry, and revocability', function (): void {
    $admin = User::factory()->admin()->create();
    $unused = PlacementAccessCode::factory()->create(['issued_by' => $admin->id]);
    PlacementAccessCode::factory()->inProgress()->create();
    PlacementAccessCode::factory()->completed()->create();
    PlacementAccessCode::factory()->revoked()->create();
    $expired = PlacementAccessCode::factory()->expired()->create();

    $this->actingAs($admin)
        ->get(route('admin.access-codes.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glc/admin/access-codes/index')
            ->has('codes.data', 5)
            ->has('statuses', 4));

    $this->actingAs($admin)
        ->get(route('admin.access-codes.index', ['status' => 'revoked']))
        ->assertInertia(fn ($page) => $page
            ->has('codes.data', 1)
            ->where('codes.data.0.status', 'revoked')
            ->where('codes.data.0.can_revoke', false));

    $this->actingAs($admin)
        ->get(route('admin.access-codes.index', ['search' => $expired->code]))
        ->assertInertia(fn ($page) => $page
            ->has('codes.data', 1)
            ->where('codes.data.0.is_expired', true));

    $this->actingAs($admin)
        ->get(route('admin.access-codes.index', ['search' => $unused->code]))
        ->assertInertia(fn ($page) => $page
            ->where('codes.data.0.issuer_name', $admin->name)
            ->where('codes.data.0.can_revoke', true));
});

it('creates a single access code with note and expiry and audits it', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.access-codes.store'), [
            'quantity' => 1,
            'expires_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            'note' => 'June intake',
        ])
        ->assertRedirectToRoute('admin.access-codes.index');

    $code = PlacementAccessCode::query()->firstOrFail();

    expect($code->status)->toBe(PlacementAccessCodeStatus::Unused)
        ->and(mb_strlen($code->code))->toBe(8)
        ->and($code->code)->toBe(mb_strtoupper($code->code))
        ->and($code->issued_by)->toBe($admin->id)
        ->and($code->note)->toBe('June intake')
        ->and($code->expires_at)->not->toBeNull()
        ->and($code->isUsable())->toBeTrue();

    $log = AuditLog::query()->where('action', AuditAction::AccessCodeCreated)->firstOrFail();

    expect($log->subject_id)->toBe($code->id)
        ->and($log->details['code'])->toBe($code->code);
});

it('creates a batch of codes with one audit entry per code', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.access-codes.store'), ['quantity' => 5])
        ->assertRedirectToRoute('admin.access-codes.index');

    expect(PlacementAccessCode::query()->count())->toBe(5)
        ->and(PlacementAccessCode::query()->distinct('code')->count('code'))->toBe(5)
        ->and(AuditLog::query()->where('action', AuditAction::AccessCodeCreated)->count())->toBe(5);
});

it('validates quantity bounds and future expiry', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.access-codes.store'), ['quantity' => 0])
        ->assertSessionHasErrors('quantity');

    $this->actingAs($admin)
        ->post(route('admin.access-codes.store'), ['quantity' => 101])
        ->assertSessionHasErrors('quantity');

    $this->actingAs($admin)
        ->post(route('admin.access-codes.store'), [
            'quantity' => 1,
            'expires_at' => now()->subDay()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHasErrors('expires_at');

    expect(PlacementAccessCode::query()->count())->toBe(0);
});

it('revokes unused and in-progress codes and audits the revocation', function (?string $factoryState): void {
    $admin = User::factory()->admin()->create();

    $factory = PlacementAccessCode::factory();

    if ($factoryState !== null) {
        $factory = $factory->{$factoryState}();
    }

    $code = $factory->create();

    $this->actingAs($admin)
        ->patch(route('admin.access-codes.revoke', $code))
        ->assertSessionHasNoErrors();

    $code->refresh();

    expect($code->status)->toBe(PlacementAccessCodeStatus::Revoked)
        ->and($code->revoked_at)->not->toBeNull()
        ->and($code->isUsable())->toBeFalse();

    expect(AuditLog::query()
        ->where('action', AuditAction::AccessCodeRevoked)
        ->where('subject_id', $code->id)
        ->exists())->toBeTrue();
})->with(['unused' => [null], 'in progress' => ['inProgress']]);

it('cannot revoke completed codes or re-revoke revoked codes', function (): void {
    $admin = User::factory()->admin()->create();

    $completed = PlacementAccessCode::factory()->completed()->create();
    $revoked = PlacementAccessCode::factory()->revoked()->create();
    $revokedAt = $revoked->revoked_at;

    $this->actingAs($admin)
        ->patch(route('admin.access-codes.revoke', $completed))
        ->assertSessionHasErrors('code');

    expect($completed->refresh()->status)->toBe(PlacementAccessCodeStatus::Completed);

    $this->actingAs($admin)
        ->patch(route('admin.access-codes.revoke', $revoked))
        ->assertSessionHasErrors('code');

    expect($revoked->refresh()->status)->toBe(PlacementAccessCodeStatus::Revoked)
        ->and($revoked->revoked_at->equalTo($revokedAt))->toBeTrue();

    expect(AuditLog::query()->where('action', AuditAction::AccessCodeRevoked)->count())->toBe(0);
});

it('keeps expired codes unusable even before revocation', function (): void {
    $code = PlacementAccessCode::factory()->expired()->create();

    expect($code->isExpired())->toBeTrue()
        ->and($code->isUsable())->toBeFalse();
});
