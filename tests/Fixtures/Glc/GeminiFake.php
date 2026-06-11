<?php

declare(strict_types=1);

namespace Tests\Fixtures\Glc;

final class GeminiFake
{
    /**
     * @param  list<string>  $citations
     * @return array<string, mixed>
     */
    public static function chat(string $reply, ?string $violation = null, array $citations = []): array
    {
        $response = self::text((string) json_encode(['reply' => $reply, 'violation' => $violation]));

        if ($citations !== []) {
            $response['candidates'][0]['groundingMetadata'] = [
                'groundingChunks' => array_map(
                    fn (string $title): array => ['retrievedContext' => ['title' => $title, 'uri' => 'fileSearchStores/x/documents/y']],
                    $citations,
                ),
            ];
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    public static function text(string $text): array
    {
        return [
            'candidates' => [
                ['content' => ['parts' => [['text' => $text]]]],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function writing(array $overrides = []): array
    {
        $payload = array_merge([
            'dimensions' => [
                'grammar' => ['score' => 3, 'comment' => 'Watch your verb tenses.'],
                'vocabulary' => ['score' => 4, 'comment' => 'Good word variety.'],
                'structure' => ['score' => 3, 'comment' => 'Add clearer paragraphs.'],
                'coherence' => ['score' => 4, 'comment' => 'Ideas connect well.'],
                'task_completion' => ['score' => 5, 'comment' => 'Fully addresses the task.'],
            ],
            'summary' => 'A solid attempt. Focus on grammar accuracy next.',
            'highlights' => [
                ['start' => 0, 'end' => 5, 'type' => 'grammar', 'comment' => 'Check the verb form here.'],
            ],
        ], $overrides);

        return self::text((string) json_encode($payload));
    }
}
