<?php

declare(strict_types=1);

namespace App\Services\Glc\Placement;

use App\Services\Glc\Review\GeminiClient;
use Throwable;

final readonly class MicCheckTranscriber
{
    public function __construct(private GeminiClient $gemini) {}

    public function transcribe(string $audio, string $mimeType): ?string
    {
        $apiKey = config('gemini.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            return null;
        }

        $prompt = <<<'PROMPT'
Transcribe the short microphone-check recording attached as audio, exactly as spoken.
If the audio contains no intelligible speech, return an empty transcript.
Respond with JSON only.
PROMPT;

        try {
            $result = $this->gemini->generateJson(
                [
                    ['text' => $prompt],
                    ['inlineData' => [
                        'mimeType' => $mimeType,
                        'data' => base64_encode($audio),
                    ]],
                ],
                [
                    'type' => 'OBJECT',
                    'properties' => ['transcript' => ['type' => 'STRING']],
                    'required' => ['transcript'],
                ],
            );
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        $transcript = $result['transcript'] ?? null;

        return is_string($transcript) && mb_trim($transcript) !== '' ? mb_trim($transcript) : null;
    }
}
