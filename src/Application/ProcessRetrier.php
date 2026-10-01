<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Application;

use Oton\ProcessTracker\Domain\ProcessId;
use Oton\ProcessTracker\Domain\StepId;

final class ProcessRetrier
{
    public function __construct(private readonly ProcessTracker $tracker) {}

    public function retryStep(ProcessId|string $processId, StepId|string $stepId): ProcessSnapshot
    {
        $snapshot = $this->tracker->inspect($processId);
        foreach ($snapshot->steps as $step) {
            if ($step->id === (string) $stepId && $step->status->value === 'failed') {
                return $this->tracker->startStep($processId, $stepId);
            }
        }

        throw new \LogicException(sprintf('Step "%s" is not in a failed state and cannot be retried.', $stepId));
    }
}
