<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Placement;

use Inertia\Inertia;
use Inertia\Response;

final readonly class SessionStatusController
{
    public function blocked(): Response
    {
        return Inertia::render('glc/placement/blocked', [
            'minimumAge' => config()->integer('glc.placement.minimum_age', 12),
        ]);
    }

    public function expired(): Response
    {
        return Inertia::render('glc/placement/expired', [
            'resumeWindowHours' => config()->integer('glc.placement.resume_window_hours', 24),
        ]);
    }

    public function terminated(): Response
    {
        return Inertia::render('glc/placement/terminated');
    }
}
