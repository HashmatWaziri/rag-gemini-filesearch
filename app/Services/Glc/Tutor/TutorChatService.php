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
use App\Services\Glc\Admin\TutorOperationalSettings;
use App\Services\Glc\Ai\PlacementAiSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

final class TutorChatService
{
    public const UNAVAILABLE_MESSAGE = 'The tutor is temporarily unavailable. Please try again in a few minutes. Your message has been saved.';

    public const UNASSIGNED_MESSAGE = 'Your teacher has not set up your course yet. Please ask your teacher or GLC to set it up, then come back and try again.';

    public const MATERIALS_NOT_READY_MESSAGE = "Your study materials aren't ready yet — please check back soon or contact your teacher.";

    public function __construct(
        private readonly PlacementAiSettings $aiSettings,
        private readonly TutorSystemPrompt $systemPrompt,
        private readonly TutorViolationRecorder $violations,
        private readonly TutorOperationalSettings $operationalSettings,
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
            return $this->finishWithoutModelCall($conversation, self::UNASSIGNED_MESSAGE);
        }

        if (! $this->hasPublishedMaterials($assignment)) {
            return $this->finishWithoutModelCall($conversation, self::MATERIALS_NOT_READY_MESSAGE);
        }

        if (! $this->storeIsConfigured()) {
            return $this->finishWithoutModelCall($conversation, self::MATERIALS_NOT_READY_MESSAGE);
        }

        [$reply, $violation, $citations] = $this->generateReply($conversation, $assignment, $text);

        $assistantMessage = $this->persistAssistantMessage($conversation, $reply, $violation, $citations);

        if ($violation instanceof TutorViolationCategory) {
            $this->violations->record($conversation, $userMessage, $violation);
        }

        $conversation->last_activity_at = now();
        $conversation->save();

        $settings = $this->operationalSettings->effective();

        if ($this->activePairCount($conversation) > $settings['rotation_threshold_pairs']) {
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

    public function hasPublishedMaterials(StudentAssignment $assignment): bool
    {
        return CurriculumDocument::query()
            ->tutorRetrievable()
            ->withinAssignment($assignment)
            ->exists();
    }

    private function storeIsConfigured(): bool
    {
        $storeName = Setting::get(SettingKey::GlcCurriculumStoreName);

        return is_string($storeName) && $storeName !== '';
    }

    private function finishWithoutModelCall(TutorConversation $conversation, string $reply): TutorMessage
    {
        $assistantMessage = $this->persistAssistantMessage($conversation, $reply, null, []);
        $conversation->last_activity_at = now();
        $conversation->save();

        return $assistantMessage;
    }

    /**
     * @return array{0: string, 1: TutorViolationCategory|null, 2: list<string>}
     */
    private function generateReply(TutorConversation $conversation, StudentAssignment $assignment, string $text): array
    {
        if (! $this->aiSettings->taskIsConfigured(PlacementAiSettings::TASK_TUTOR_CHAT)) {
            return [self::UNAVAILABLE_MESSAGE, null, []];
        }

        try {
            $this->aiSettings->hydrateProviderConfig();
            $selection = $this->aiSettings->selection(PlacementAiSettings::TASK_TUTOR_CHAT);

            $agent = new GlcTutorAgent(
                student: $conversation->user,
                assignment: $assignment,
                conversation: $conversation,
                systemPrompt: $this->systemPrompt,
            );

            $response = $agent->prompt(
                $text,
                provider: $selection['provider'],
                model: $selection['model'],
            );

            if (! $response instanceof StructuredAgentResponse) {
                return [self::UNAVAILABLE_MESSAGE, null, []];
            }

            $reply = is_string($response['reply'] ?? null) ? $response['reply'] : null;

            if ($reply === null || $reply === '') {
                return [self::UNAVAILABLE_MESSAGE, null, []];
            }

            $rawViolation = $response['violation'] ?? null;
            $violation = is_string($rawViolation) ? TutorViolationCategory::tryFrom($rawViolation) : null;

            return [$reply, $violation, $this->formatCitations($agent->citationTitles, $assignment)];
        } catch (Throwable) {
            return [self::UNAVAILABLE_MESSAGE, null, []];
        }
    }

    /**
     * @param  list<string>  $titles
     * @return list<string>
     */
    private function formatCitations(array $titles, StudentAssignment $assignment): array
    {
        if ($titles === []) {
            return [];
        }

        $assignment->loadMissing(['course', 'unit']);

        $documents = CurriculumDocument::query()
            ->tutorRetrievable()
            ->withinAssignment($assignment)
            ->whereIn('title', $titles)
            ->with('lesson')
            ->get()
            ->keyBy('title');

        return array_map(
            function (string $title) use ($assignment, $documents): string {
                $document = $documents->get($title);

                return sprintf(
                    '%s (%s / %s / %s)',
                    $title,
                    $assignment->course->name,
                    $assignment->unit->name,
                    $document?->lesson?->name ?? 'Unit-wide',
                );
            },
            $titles,
        );
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
