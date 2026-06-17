<?php

declare(strict_types=1);

namespace App\Exceptions\Glc;

use Carbon\CarbonInterface;
use RuntimeException;

final class GlcAiCostLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly string $limitType,
        public readonly float $currentUsd,
        public readonly float $limitUsd,
        public readonly CarbonInterface $resetsAt,
    ) {
        parent::__construct(sprintf(
            'GLC AI cost limit exceeded for %s window ($%.4f / $%.2f).',
            $this->limitType,
            $this->currentUsd,
            $this->limitUsd,
        ));
    }
}
