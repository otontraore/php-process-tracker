<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Application;

use DateTimeImmutable;
use Oton\ProcessTracker\Domain\ProcessStatus;

final readonly class ProcessSnapshot
{
    /** @param list<StepSnapshot> $steps @param array<string, mixed> $metadata */
    public function __construct(
        public string $id,
        public string $type,
        public ProcessStatus $status,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $finishedAt,
        public ?string $failureMessage,
        public ?string $subjectType,
        public ?string $subjectId,
        public array $metadata,
        public array $steps,
        public ?string $correlationId = null,
        public ?string $causationId = null,
    ) {}
}
