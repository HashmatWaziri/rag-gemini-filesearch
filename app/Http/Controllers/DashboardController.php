<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Glc\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function show(Request $request): Response|RedirectResponse
    {
        $role = $request->user()?->role;

        if ($role instanceof UserRole) {
            return redirect($role->homePath());
        }

        return Inertia::render('dashboard');
    }
}
