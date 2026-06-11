<?php

declare(strict_types=1);

namespace App\Enums\Glc;

enum UserRole: string
{
    case Admin = 'admin';
    case AcademicSupervisor = 'academic_supervisor';
    case Teacher = 'teacher';
    case Student = 'student';

    /**
     * @return list<string>
     */
    public static function staffValues(): array
    {
        return [self::Admin->value, self::AcademicSupervisor->value, self::Teacher->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::AcademicSupervisor => 'Academic Supervisor',
            self::Teacher => 'Teacher',
            self::Student => 'Student',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Student;
    }

    public function canManageUsers(): bool
    {
        return $this === self::Admin;
    }

    public function canManageCurriculum(): bool
    {
        return $this === self::Admin || $this === self::AcademicSupervisor;
    }

    public function canReviewPlacements(): bool
    {
        return $this->isStaff();
    }

    public function canViewAllStudents(): bool
    {
        return $this === self::Admin || $this === self::AcademicSupervisor;
    }

    public function homePath(): string
    {
        return match ($this) {
            self::Admin => '/admin/users',
            self::AcademicSupervisor, self::Teacher => '/staff/review',
            self::Student => '/tutor',
        };
    }
}
