<?php

declare(strict_types=1);

namespace App\Services\Glc\Review;

use RuntimeException;
use Throwable;

final class SpeakingEvaluationFailed extends RuntimeException
{
    public function __construct(public readonly string $transcript, Throwable $previous)
    {
        parent::__construct($previous->getMessage(), previous: $previous);
    }
}
