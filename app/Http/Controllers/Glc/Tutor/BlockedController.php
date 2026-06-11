<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Tutor;

use Inertia\Inertia;
use Inertia\Response;

final readonly class BlockedController
{
    public function __invoke(): Response
    {
        return Inertia::render('glc/tutor/blocked');
    }
}
