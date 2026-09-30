<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Application;

use DateTimeImmutable;
use Oton\ProcessTracker\Domain\StepStatus;

final readonly class StepSnapshot
{
    public function __construct(
        public string $id,
        public string $name,
        public StepStatus $status,
        public int $attempt,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $finishedAt,
        public ?string $failureMessage,
        public ?string $failureCode,
    ) {
    }
}
