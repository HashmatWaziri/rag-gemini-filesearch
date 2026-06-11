<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Services\Glc\Admin\TutorOperationalSettings;
use App\Services\Glc\Ai\PlacementAiSettings;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

final class ConversationRotator
{
    public function __construct(
        private readonly PlacementAiSettings $aiSettings,
        private readonly TutorOperationalSettings $operationalSettings,
    ) {}

    public function rotate(TutorConversation $conversation): void
    {
        $settings = $this->operationalSettings->effective();
        $threshold = $settings['rotation_threshold_pairs'];
        $summarizePairs = $settings['rotation_summarize_pairs'];

        $active = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->reject(fn (TutorMessage $message): bool => (bool) data_get($message->metadata, 'rotated', false))
            ->values();

        if (intdiv($active->count(), 2) <= $threshold) {
            return;
        }

        $oldest = $active->take($summarizePairs * 2);

        $transcript = $oldest
            ->map(fn (TutorMessage $message): string => ($message->role === 'assistant' ? 'Tutor: ' : 'Student: ').$message->content)
            ->implode("\n");

        $summary = $this->summarize($transcript);

        if ($summary === null) {
            return;
        }

        $existing = is_string($conversation->summary) && $conversation->summary !== ''
            ? $conversation->summary."\n\n"
            : '';

        $conversation->update(['summary' => $existing.mb_trim($summary)]);

        foreach ($oldest as $message) {
            $message->update(['metadata' => array_merge($message->metadata ?? [], ['rotated' => true])]);
        }
    }

    private function summarize(string $transcript): ?string
    {
        if (! $this->aiSettings->taskIsConfigured(PlacementAiSettings::TASK_TUTOR_CHAT)) {
            return null;
        }

        try {
            $this->aiSettings->hydrateProviderConfig();
            $selection = $this->aiSettings->selection(PlacementAiSettings::TASK_TUTOR_CHAT);

            $response = new TutorConversationSummarizerAgent()->prompt(
                "Summarize this tutoring conversation excerpt:\n\n".$transcript,
                provider: $selection['provider'],
                model: $selection['model'],
            );

            if (! $response instanceof StructuredAgentResponse) {
                return null;
            }

            $summary = $response['summary'] ?? null;

            return is_string($summary) && $summary !== '' ? $summary : null;
        } catch (Throwable) {
            return null;
        }
    }
}
