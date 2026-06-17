<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Tutor;

use App\Services\Glc\Tutor\TutorSetupService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class TutorSetupController
{
    public function __construct(private TutorSetupService $setup) {}

    public function index(Request $request): Response
    {
        return Inertia::render('glc/tutor/staff/setup', $this->setup->payload($request->user(), $request));
    }
}
