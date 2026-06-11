<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class GeminiClient
{
    /**
     * @param  list<array<string, mixed>>  $parts
     * @param  array<string, mixed>  $responseSchema
     * @return array<string, mixed>
     */
    public function generateJson(array $parts, array $responseSchema): array
    {
        $apiKey = config('gemini.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $baseUrl = config()->string('gemini.base_url');
        $model = config()->string('glc.ai_drafts.model');

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout(config()->integer('gemini.request_timeout', 30))
            ->post(sprintf('%s/models/%s:generateContent', $baseUrl, $model), [
                'contents' => [['parts' => $parts]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => $responseSchema,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf('Gemini request failed with HTTP %d.', $response->status()));
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            throw new RuntimeException('Gemini response contained no content.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned unparseable JSON.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
