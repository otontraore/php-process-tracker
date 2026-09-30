<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Domain;

final class InMemoryProcessRepository implements ProcessRepository
{
    /** @var array<string, Process> */
    private array $processes = [];

    public function save(Process $process): void
    {
        $this->processes[(string) $process->id()] = $process;
    }

    public function get(ProcessId $id): Process
    {
        return $this->processes[(string) $id] ?? throw new \OutOfBoundsException(
            sprintf('Process "%s" does not exist.', $id)
        );
    }

    public function has(ProcessId $id): bool
    {
        return isset($this->processes[(string) $id]);
    }
}
