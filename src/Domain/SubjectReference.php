<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Domain;

final readonly class SubjectReference
{
    public function __construct(
        public string $type,
        public string $id,
    ) {
        if ($type === '' || $id === '') {
            throw new \InvalidArgumentException('Subject type and ID cannot be empty.');
        }
    }
}
