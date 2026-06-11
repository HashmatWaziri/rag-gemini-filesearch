<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Models\User;
use App\Services\Glc\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final readonly class UserAnonymizeController
{
    private const array REDACTED_FIELDS = ['name', 'email', 'guardian_name', 'guardian_email'];

    public function __construct(private AuditLogger $auditLogger) {}

    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isGlcStudent(), 404);

        $user->update([
            'name' => 'Anonymized Student',
            'email' => sprintf('anonymized-%d@redacted.invalid', $user->id),
            'guardian_name' => $user->guardian_name === null ? null : 'Redacted Guardian',
            'guardian_email' => $user->guardian_email === null ? null : sprintf('redacted-guardian-%d@redacted.invalid', $user->id),
        ]);

        $this->auditLogger->log(AuditAction::UserAnonymized, $request->user(), $user, [
            'fields' => self::REDACTED_FIELDS,
        ]);

        return to_route('admin.users.edit', $user)->with('glc_status', 'Student record anonymized.');
    }
}
