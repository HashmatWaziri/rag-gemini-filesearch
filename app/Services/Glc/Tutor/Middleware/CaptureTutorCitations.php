<?php

declare(strict_types=1);

namespace App\Services\Glc\Tutor\Middleware;

use App\Services\Glc\Tutor\GlcTutorAgent;
use App\Services\Glc\Tutor\TutorCitationExtractor;
use Closure;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

final readonly class CaptureTutorCitations
{
    public function __construct(private TutorCitationExtractor $citations) {}

    public function handle(AgentPrompt $prompt, Closure $next): AgentResponse
    {
        return $next($prompt)->then(function (AgentResponse $response) use ($prompt): void {
            if ($prompt->agent instanceof GlcTutorAgent) {
                $prompt->agent->citationTitles = $this->citations->titlesFromResponse($response);
            }
        });
    }
}
