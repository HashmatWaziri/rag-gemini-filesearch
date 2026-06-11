<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;

final class ConversationRotator
{
    public function __construct(private readonly GeminiTutorClient $client) {}

    public function rotate(TutorConversation $conversation): void
    {
        $threshold = config()->integer('glc.tutor.rotation_threshold_pairs', 40);
        $summarizePairs = config()->integer('glc.tutor.rotation_summarize_pairs', 20);

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
        $response = $this->client->generateContent([
            'system_instruction' => [
                'parts' => [[
                    'text' => 'You summarize English tutoring conversations. Produce a concise English summary (under 200 words) of the excerpt: topics covered, concepts the student struggled with, and any commitments or progress. Output plain text only.',
                ]],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => "Summarize this tutoring conversation excerpt:\n\n".$transcript]],
            ]],
        ]);

        if ($response === null) {
            return null;
        }

        return $this->client->extractText($response);
    }
}
