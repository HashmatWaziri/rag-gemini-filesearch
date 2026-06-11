<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\UrlCitation;

class TutorCitationExtractor
{
    /**
     * @return list<string>
     */
    public function titlesFromResponse(AgentResponse $response): array
    {
        $titles = collect();

        foreach ($response->meta->citations as $citation) {
            if ($citation instanceof UrlCitation && is_string($citation->title) && $citation->title !== '') {
                $titles->push($citation->title);
            }
        }

        foreach ($response->steps as $step) {
            foreach ($step->meta->citations as $citation) {
                if ($citation instanceof UrlCitation && is_string($citation->title) && $citation->title !== '') {
                    $titles->push($citation->title);
                }
            }
        }

        if ($titles->isEmpty()) {
            $titles = collect($this->titlesFromRecordedHttpResponses());
        }

        return $titles->unique()->values()->all();
    }

    /**
     * @param  array<string, mixed>  $response
     * @return list<string>
     */
    public function titlesFromGroundingMetadata(array $response): array
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

    /**
     * @return list<string>
     */
    private function titlesFromRecordedHttpResponses(): array
    {
        $titles = [];

        $recorded = Http::recorded();

        if ($recorded instanceof Collection) {
            $recorded = $recorded->all();
        }

        foreach (array_reverse($recorded) as $record) {
            [$request, $httpResponse] = $record;

            if (! str_contains($request->url(), 'generativelanguage.googleapis.com')) {
                continue;
            }

            $json = $httpResponse->json();

            if (is_array($json)) {
                $titles = [...$titles, ...$this->titlesFromGroundingMetadata($json)];
            }
        }

        return array_values(array_unique($titles));
    }
}
