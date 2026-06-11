<?php

declare(strict_types=1);

namespace App\Http\Middleware\Glc;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTutorAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isGlcStudent()) {
            abort(403);
        }

        if ($user->requiresGuardianConsent() && ! $user->hasGuardianConsent()) {
            return redirect()->route('tutor.blocked');
        }

        return $next($request);
    }
}
