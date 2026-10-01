<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Application;

use Oton\ProcessTracker\Domain\ProcessId;
use Oton\ProcessTracker\Domain\ProcessStatus;

final class ProcessInspector
{
    public function __construct(private readonly ProcessTracker $tracker) {}

    public function inspect(ProcessId|string $processId): ProcessSnapshot
    {
        return $this->tracker->inspect($processId);
    }

    public function isTerminal(ProcessId|string $processId): bool
    {
        return $this->inspect($processId)->status->isTerminal();
    }

    public function isFailed(ProcessId|string $processId): bool
    {
        return $this->inspect($processId)->status === ProcessStatus::Failed;
    }
}
