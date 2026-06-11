<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class GeminiTutorClient
{
    public function isConfigured(): bool
    {
        $apiKey = config('gemini.api_key');

        return is_string($apiKey) && $apiKey !== '';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    public function generateContent(array $payload): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $baseUrl = config('gemini.base_url');
        $model = config('glc.tutor.model');

        $url = sprintf(
            '%s/models/%s:generateContent',
            is_string($baseUrl) ? $baseUrl : '',
            is_string($model) && $model !== '' ? $model : 'gemini-2.5-flash',
        );

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => config('gemini.api_key'),
            ])
                ->timeout(config()->integer('gemini.request_timeout', 30))
                ->post($url, $payload);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $json = $response->json();

        return is_array($json) ? $json : null;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function extractText(array $response): ?string
    {
        $text = data_get($response, 'candidates.0.content.parts.0.text');

        return is_string($text) && $text !== '' ? $text : null;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return list<string> unique retrieved-document titles from grounding metadata
     */
    public function extractCitations(array $response): array
    {
        $chunks = data_get($response, 'candidates.0.groundingMetadata.groundingChunks');

        if (! is_array($chunks)) {
            return [];
        }

        return collect($chunks)
            ->map(fn (mixed $chunk): mixed => data_get($chunk, 'retrievedContext.title'))
            ->filter(fn (mixed $title): bool => is_string($title) && $title !== '')
            ->unique()
            ->values()
            ->all();
    }
}
