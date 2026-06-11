<?php

declare(strict_types=1);

namespace App\Http\Middleware\Glc;

use App\Enums\Glc\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        $role = $user->role;

        if (! $role instanceof UserRole || ! in_array($role->value, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
