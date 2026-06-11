<?php

declare(strict_types=1);

namespace App\Http\Controllers\Glc\Tutor;

use App\Models\Glc\WritingSubmission;
use App\Models\User;
use App\Services\Glc\Tutor\WritingCorrectionService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final readonly class WritingController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private WritingCorrectionService $correction,
    ) {}

    public function index(): Response
    {
        $submissions = $this->user->writingSubmissions()
            ->get()
            ->map(fn (WritingSubmission $submission): array => [
                'id' => $submission->id,
                'status' => $submission->status,
                'excerpt' => Str::limit($submission->text, 90),
                'created_at' => $submission->created_at?->toIso8601String(),
            ])
            ->all();

        return Inertia::render('glc/tutor/writing/index', [
            'submissions' => $submissions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var array{text: string} $validated */
        $validated = $request->validate([
            'text' => ['required', 'string', 'min:20', 'max:10000'],
        ]);

        /** @var WritingSubmission $submission */
        $submission = $this->user->writingSubmissions()->create([
            'text' => $validated['text'],
            'status' => 'pending',
        ]);

        $this->correction->evaluate($submission);

        return redirect()->route('tutor.writing.show', $submission);
    }

    public function show(WritingSubmission $submission): Response
    {
        abort_unless($submission->user_id === $this->user->id, 403);

        return Inertia::render('glc/tutor/writing/show', [
            'submission' => [
                'id' => $submission->id,
                'status' => $submission->status,
                'text' => $submission->text,
                'feedback' => $submission->feedback,
                'highlights' => $submission->highlights ?? [],
                'error' => $submission->error,
                'created_at' => $submission->created_at?->toIso8601String(),
            ],
        ]);
    }
}
