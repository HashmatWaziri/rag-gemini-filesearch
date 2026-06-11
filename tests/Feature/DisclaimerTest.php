<?php

declare(strict_types=1);

use App\Http\Controllers\DisclaimerController;
use App\Models\User;

use function Pest\Laravel\actingAs;

covers(DisclaimerController::class);

it('renders disclaimer page for user who has not accepted', function (): void {
    $this->withoutVite();

    $user = User::factory()->withoutDisclaimer()->create();

    actingAs($user)
        ->get(route('disclaimer.show'))
        ->assertOk();
});

it('redirects to disclaimer page when user has not accepted', function (): void {
    $user = User::factory()->create([
        'accepted_disclaimer_at' => null,
    ]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('disclaimer.show'));
});

it('allows access to dashboard when disclaimer is accepted', function (): void {
    $user = User::factory()->create([
        'accepted_disclaimer_at' => now(),
    ]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

it('accepts disclaimer and redirects to dashboard', function (): void {
    $user = User::factory()->create([
        'accepted_disclaimer_at' => null,
    ]);

    actingAs($user)
        ->post(route('disclaimer.accept'), ['accepted' => '1'])
        ->assertRedirect();

    $user->refresh();
    expect($user->accepted_disclaimer_at)->not->toBeNull();
});
