<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use App\Enums\Glc\TutorViolationCategory;
use App\Enums\SettingKey;
use App\Models\Glc\StudentAssignment;
use App\Models\Glc\TutorConversation;
use App\Models\Glc\TutorMessage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\FileSearch;
use Laravel\Ai\Providers\Tools\FileSearchQuery;

final class GlcTutorAgent implements Agent, Conversational, HasMiddleware, HasStructuredOutput, HasTools
{
    use Promptable;

    /** @var list<string> */
    public array $citationTitles = [];

    public function __construct(
        public User $student,
        public StudentAssignment $assignment,
        public TutorConversation $conversation,
        private readonly TutorSystemPrompt $systemPrompt,
    ) {}

    public function instructions(): string
    {
        return $this->systemPrompt->build($this->student, $this->assignment);
    }

    /**
     * @return array<int, mixed>
     */
    public function middleware(): array
    {
        return [Middleware\CaptureTutorCitations::class];
    }

    /**
     * @return iterable<int, UserMessage|AssistantMessage>
     */
    public function messages(): iterable
    {
        $messages = [];

        if (is_string($this->conversation->summary) && $this->conversation->summary !== '') {
            $messages[] = new UserMessage(
                "Summary of the earlier part of this conversation:\n".$this->conversation->summary,
            );
            $messages[] = new AssistantMessage('Understood. I will keep that earlier context in mind.');
        }

        $active = $this->activeMessages();

        if ($active->isEmpty()) {
            return $messages;
        }

        $history = $active->slice(0, max(0, $active->count() - 1));

        foreach ($history as $message) {
            $messages[] = $message->role === 'assistant'
                ? new AssistantMessage($message->content)
                : new UserMessage($message->content);
        }

        return $messages;
    }

    public function tools(): iterable
    {
        $storeName = Setting::get(SettingKey::GlcCurriculumStoreName);

        if (! is_string($storeName) || $storeName === '') {
            return [];
        }

        return [
            new FileSearch(
                stores: [$storeName],
                where: fn (FileSearchQuery $query) => $query
                    ->where('course_id', $this->assignment->course_id)
                    ->where('course_level_id', $this->assignment->course_level_id)
                    ->where('course_unit_id', $this->assignment->course_unit_id)
                    ->where('status', 'published'),
            ),
        ];
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'reply' => $schema->string()->required(),
            'violation' => $schema->string()
                ->enum(array_map(
                    fn (TutorViolationCategory $category): string => $category->value,
                    TutorViolationCategory::cases(),
                ))
                ->nullable(),
        ];
    }

    /**
     * @return Collection<int, TutorMessage>
     */
    private function activeMessages(): Collection
    {
        return $this->conversation->messages()
            ->orderBy('id')
            ->get()
            ->reject(fn (TutorMessage $message): bool => (bool) data_get($message->metadata, 'rotated', false))
            ->values();
    }
}
