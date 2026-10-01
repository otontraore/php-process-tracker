<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Persistence;

use Oton\ProcessTracker\Domain\Process;

interface ProcessSerializer
{
    /** @return array<string, mixed> */
    public function serialize(Process $process): array;

    /** @param array<string, mixed> $data */
    public function deserialize(array $data): Process;
}
