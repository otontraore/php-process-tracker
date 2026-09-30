<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Domain;

interface ProcessRepository
{
    public function save(Process $process): void;

    public function get(ProcessId $id): Process;

    public function has(ProcessId $id): bool;
}
