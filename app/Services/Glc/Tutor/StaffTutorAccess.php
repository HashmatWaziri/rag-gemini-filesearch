<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Enums\Glc\UserRole;
use App\Models\User;

final class StaffTutorAccess
{
    public function canViewStudent(User $staff, User $student): bool
    {
        if (! $student->isGlcStudent()) {
            return false;
        }

        $role = $staff->role;

        if (! $role instanceof UserRole || ! $role->isStaff()) {
            return false;
        }

        if ($role->canViewAllStudents()) {
            return true;
        }

        return $staff->assignedStudents()->whereKey($student->id)->exists();
    }

    public function authorizeStudent(User $staff, User $student): void
    {
        abort_unless($student->isGlcStudent(), 404);
        abort_unless($this->canViewStudent($staff, $student), 403);
    }
}
