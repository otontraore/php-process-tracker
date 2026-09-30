<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Domain;

use DateTimeImmutable;

final readonly class Failure
{
    public function __construct(
        public string $message,
        public DateTimeImmutable $occurredAt,
        public ?string $code = null,
    ) {
        if ($message === '') {
            throw new \InvalidArgumentException('Failure message cannot be empty.');
        }
    }
}
