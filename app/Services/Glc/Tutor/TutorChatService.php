<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Enums\Glc\TutorViolationCategory;
use App\Enums\SettingKey;
use App\Jobs\Glc\Tutor\RotateConversationJob;
use App\Models\Glc\CurriculumDocument;
use App\Models\Glc\StudentAssignment;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class TutorChatService
{
    public const UNAVAILABLE_MESSAGE = 'The tutor is temporarily unavailable. Please try again in a few minutes. Your message has been saved.';

    public const UNASSIGNED_MESSAGE = 'You do not have a course assignment yet. Please ask your teacher or a GLC admin to assign your course, level, and unit.';

    public function __construct(
        private readonly GeminiTutorClient $client,
        private readonly TutorSystemPrompt $systemPrompt,
        private readonly TutorViolationRecorder $violations,
    ) {}

    public function respond(TutorConversation $conversation, string $text): TutorMessage
    {
        $student = $conversation->user;
        $assignment = $student->studentAssignment;

        /** @var TutorMessage $userMessage */
        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $text,
        ]);

        if ($conversation->title === null) {
            $conversation->title = Str::limit(mb_trim($text), 60);
        }

        if (! $assignment instanceof StudentAssignment) {
            $assistantMessage = $this->persistAssistantMessage($conversation, self::UNASSIGNED_MESSAGE, null, []);
            $conversation->last_activity_at = now();
            $conversation->save();

            return $assistantMessage;
        }

        [$reply, $violation, $citations] = $this->generateReply($conversation, $assignment);

        $assistantMessage = $this->persistAssistantMessage($conversation, $reply, $violation, $citations);

        if ($violation instanceof TutorViolationCategory) {
            $this->violations->record($conversation, $userMessage, $violation);
        }

        $conversation->last_activity_at = now();
        $conversation->save();

        if ($this->activePairCount($conversation) > config()->integer('glc.tutor.rotation_threshold_pairs', 40)) {
            RotateConversationJob::dispatch($conversation);
        }

        return $assistantMessage;
    }

    public function metadataFilter(StudentAssignment $assignment): string
    {
        return sprintf(
            'course_id=%d AND course_level_id=%d AND course_unit_id=%d AND status="published"',
            $assignment->course_id,
            $assignment->course_level_id,
            $assignment->course_unit_id,
        );
    }

    /**
     * @return array{0: string, 1: TutorViolationCategory|null, 2: list<string>}
     */
    private function generateReply(TutorConversation $conversation, StudentAssignment $assignment): array
    {
        $response = $this->client->generateContent($this->buildPayload($conversation, $assignment));

        if ($response === null) {
            return [self::UNAVAILABLE_MESSAGE, null, []];
        }

        $text = $this->client->extractText($response);

        if ($text === null) {
            return [self::UNAVAILABLE_MESSAGE, null, []];
        }

        [$reply, $violation] = $this->parseStructuredReply($text);

        return [$reply, $violation, $this->client->extractCitations($response)];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(TutorConversation $conversation, StudentAssignment $assignment): array
    {
        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $this->systemPrompt->build($conversation->user, $assignment)]],
            ],
            'contents' => $this->buildContents($conversation),
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'reply' => ['type' => 'STRING'],
                        'violation' => [
                            'type' => 'STRING',
                            'enum' => array_map(
                                fn (TutorViolationCategory $category): string => $category->value,
                                TutorViolationCategory::cases(),
                            ),
                            'nullable' => true,
                        ],
                    ],
                    'required' => ['reply'],
                ],
            ],
        ];

        $storeName = Setting::get(SettingKey::GlcCurriculumStoreName);

        if (is_string($storeName) && $storeName !== '' && $this->hasPublishedDocumentsInScope($assignment)) {
            $payload['tools'] = [[
                'file_search' => [
                    'file_search_store_names' => [$storeName],
                    'metadata_filter' => $this->metadataFilter($assignment),
                ],
            ]];
        }

        return $payload;
    }

    private function hasPublishedDocumentsInScope(StudentAssignment $assignment): bool
    {
        return CurriculumDocument::query()
            ->published()
            ->withinAssignment($assignment)
            ->exists();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildContents(TutorConversation $conversation): array
    {
        $contents = [];

        if (is_string($conversation->summary) && $conversation->summary !== '') {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => "Summary of the earlier part of this conversation:\n".$conversation->summary]],
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => 'Understood. I will keep that earlier context in mind.']],
            ];
        }

        foreach ($this->activeMessages($conversation) as $message) {
            $contents[] = [
                'role' => $message->role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $message->content]],
            ];
        }

        return $contents;
    }

    /**
     * @return array{0: string, 1: TutorViolationCategory|null}
     */
    private function parseStructuredReply(string $text): array
    {
        $decoded = json_decode($text, true);

        if (is_array($decoded) && is_string($decoded['reply'] ?? null) && $decoded['reply'] !== '') {
            $rawViolation = $decoded['violation'] ?? null;
            $violation = is_string($rawViolation) ? TutorViolationCategory::tryFrom($rawViolation) : null;

            return [$decoded['reply'], $violation];
        }

        return [$text, null];
    }

    /**
     * @param  list<string>  $citations
     */
    private function persistAssistantMessage(
        TutorConversation $conversation,
        string $reply,
        ?TutorViolationCategory $violation,
        array $citations,
    ): TutorMessage {
        $metadata = [];

        if ($violation instanceof TutorViolationCategory) {
            $metadata['violation'] = $violation->value;
        }

        if ($citations !== []) {
            $metadata['citations'] = $citations;
        }

        /** @var TutorMessage $message */
        $message = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $reply,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        return $message;
    }

    /**
     * @return Collection<int, TutorMessage>
     */
    private function activeMessages(TutorConversation $conversation): Collection
    {
        return $conversation->messages()
            ->orderBy('id')
            ->get()
            ->reject(fn (TutorMessage $message): bool => (bool) data_get($message->metadata, 'rotated', false))
            ->values();
    }

    private function activePairCount(TutorConversation $conversation): int
    {
        return intdiv($this->activeMessages($conversation)->count(), 2);
    }
}
