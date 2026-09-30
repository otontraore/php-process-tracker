<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Domain;

final readonly class ProcessId
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Process ID cannot be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
