<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Models\User;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class UserConsentController
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isGlcStudent(), 404);

        $user->update([
            'guardian_consent_confirmed_at' => now(),
            'guardian_consent_confirmed_by' => $request->user()?->id,
        ]);

        $this->auditLogger->log(AuditAction::ConsentConfirmed, $request->user(), $user, [
            'guardian_name' => $user->guardian_name,
            'guardian_email' => $user->guardian_email,
        ]);

        return back()->with('glc_status', 'Guardian consent confirmed.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isGlcStudent(), 404);

        $user->update([
            'guardian_consent_confirmed_at' => null,
            'guardian_consent_confirmed_by' => null,
        ]);

        $this->auditLogger->log(AuditAction::ConsentRevoked, $request->user(), $user);

        return back()->with('glc_status', 'Guardian consent removed. The student can no longer use the AI Tutor until consent is confirmed again.');
    }
}
