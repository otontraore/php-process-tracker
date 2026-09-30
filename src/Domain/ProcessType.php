<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Domain;

final readonly class ProcessType
{
    public function __construct(public string $value)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('Process type cannot be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
