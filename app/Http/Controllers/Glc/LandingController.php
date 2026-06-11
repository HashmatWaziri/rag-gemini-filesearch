<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc;

use Inertia\Inertia;
use Inertia\Response;

final readonly class LandingController
{
    public function __invoke(): Response
    {
        return Inertia::render('glc/landing');
    }
}
