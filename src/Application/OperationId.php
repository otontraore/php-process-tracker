<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Application;

final readonly class OperationId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Operation ID cannot be empty.');
        }
    }

    public function __toString(): string { return $this->value; }
}
