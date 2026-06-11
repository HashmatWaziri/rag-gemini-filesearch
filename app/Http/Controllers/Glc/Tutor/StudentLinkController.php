<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Tutor;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class StudentLinkController
{
    public function __construct(#[CurrentUser] private User $user) {}

    public function store(User $student): RedirectResponse
    {
        abort_unless($student->isGlcStudent(), 404);

        $this->user->assignedStudents()->syncWithoutDetaching([$student->id]);

        return back();
    }

    public function destroy(User $student): RedirectResponse
    {
        abort_unless($student->isGlcStudent(), 404);

        $this->user->assignedStudents()->detach($student->id);

        return back();
    }
}
