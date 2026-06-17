<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Curriculum\Concerns;

use App\Models\User;
use App\Services\Glc\Curriculum\CurriculumPermission;
use App\Services\Glc\Curriculum\CurriculumPermissions;
use Illuminate\Http\Request;

trait AuthorizesCurriculum
{
    protected function authorizeCurriculum(Request $request, CurriculumPermission $action): void
    {
        /** @var User|null $user */
        $user = $request->user();

        abort_unless(
            $user instanceof User && app(CurriculumPermissions::class)->can($user, $action),
            403,
        );
    }
}
