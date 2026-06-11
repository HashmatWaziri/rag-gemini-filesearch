<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Admin;

use App\Enums\Glc\AuditAction;
use App\Enums\Glc\UserRole;
use App\Models\User;
use App\Services\Glc\Admin\PrivacyNotice;
use App\Services\Glc\Admin\UserRules;
use App\Services\Glc\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserController
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request): Response
    {
        $role = UserRole::tryFrom((string) $request->query('role'));
        $search = mb_trim((string) $request->query('search'));

        $users = User::query()
            ->whereNotNull('role')
            ->when($role instanceof UserRole, fn (Builder $query) => $query->where('role', $role))
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => $this->userPayload($user));

        return Inertia::render('glc/admin/users/index', [
            'users' => $users,
            'filters' => ['role' => $role?->value, 'search' => $search],
            'roles' => $this->roleOptions(),
            'privacyNotice' => PrivacyNotice::text(),
            'status' => $request->session()->get('glc_status'),
            'importResult' => $request->session()->get('glc_import_result'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(UserRules::rules($request->all()));

        $user = User::query()->create([
            ...$validated,
            'email_verified_at' => now(),
        ]);

        $this->auditLogger->log(AuditAction::UserCreated, $request->user(), $user, [
            'email' => $user->email,
            'role' => $user->role?->value,
        ]);

        return to_route('admin.users.index')->with('glc_status', "User {$user->name} created.");
    }

    public function edit(Request $request, User $user): Response
    {
        abort_unless($user->isGlcUser(), 404);

        return Inertia::render('glc/admin/users/edit', [
            'user' => $this->userPayload($user),
            'roles' => $this->roleOptions(),
            'privacyNotice' => PrivacyNotice::text(),
            'status' => $request->session()->get('glc_status'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isGlcUser(), 404);

        $validated = $request->validate(UserRules::rules($request->all(), $user->id, creating: false));

        if (($validated['password'] ?? null) === null) {
            unset($validated['password']);
        }

        $user->update($validated);

        $fields = array_values(array_diff(array_keys($user->getChanges()), ['updated_at']));

        $this->auditLogger->log(AuditAction::UserUpdated, $request->user(), $user, [
            'email' => $user->email,
            'fields' => $fields,
        ]);

        return to_route('admin.users.edit', $user)->with('glc_status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->isGlcUser(), 404);

        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => 'You cannot delete your own account.']);
        }

        $details = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->value,
        ];

        $user->delete();

        $this->auditLogger->log(AuditAction::UserDeleted, $request->user(), $user, $details);

        return to_route('admin.users.index')->with('glc_status', "User {$details['name']} deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->value,
            'role_label' => $user->role?->label(),
            'age' => $user->age,
            'guardian_name' => $user->guardian_name,
            'guardian_email' => $user->guardian_email,
            'requires_guardian_consent' => $user->requiresGuardianConsent(),
            'has_guardian_consent' => $user->hasGuardianConsent(),
            'guardian_consent_confirmed_at' => $user->guardian_consent_confirmed_at?->toIso8601String(),
            'created_at' => $user->created_at->toIso8601String(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return array_map(fn (UserRole $role): array => [
            'value' => $role->value,
            'label' => $role->label(),
        ], UserRole::cases());
    }
}
