<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Application;

final readonly class IdempotencyRecord
{
    public function __construct(
        public string $fingerprint,
        public ProcessSnapshot $result,
    ) {}
}
