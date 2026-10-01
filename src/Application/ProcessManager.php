<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Application;

use Oton\ProcessTracker\Domain\CausationId;
use Oton\ProcessTracker\Domain\CorrelationId;
use Oton\ProcessTracker\Domain\ProcessId;
use Oton\ProcessTracker\Domain\ProcessType;
use Oton\ProcessTracker\Domain\StepId;
use Oton\ProcessTracker\Domain\SubjectReference;

final class ProcessManager
{
    public function __construct(
        private readonly ProcessTracker $tracker,
        private readonly IdempotencyStore $idempotency,
    ) {}

    public function start(
        ProcessId|string $id,
        ProcessType|string $type,
        ?SubjectReference $subject = null,
        array $metadata = [],
        CorrelationId|string|null $correlationId = null,
        CausationId|string|null $causationId = null,
        ?OperationId $operationId = null,
    ): ProcessSnapshot {
        return $this->execute($operationId, 'start', [
            (string) $id, (string) $type, $subject?->type, $subject?->id, $metadata,
            $correlationId instanceof CorrelationId ? $correlationId->value : $correlationId,
            $causationId instanceof CausationId ? $causationId->value : $causationId,
        ], fn (): ProcessSnapshot => $this->tracker->start(
            $id, $type, $subject, $metadata, $correlationId, $causationId
        ));
    }

    public function addStep(ProcessId|string $processId, StepId|string $stepId, string $name, ?OperationId $operationId = null): ProcessSnapshot
    {
        return $this->execute($operationId, 'add_step', [(string) $processId, (string) $stepId, $name],
            fn (): ProcessSnapshot => $this->tracker->addStep($processId, $stepId, $name));
    }

    public function startStep(ProcessId|string $processId, StepId|string $stepId, ?OperationId $operationId = null): ProcessSnapshot
    {
        return $this->execute($operationId, 'start_step', [(string) $processId, (string) $stepId],
            fn (): ProcessSnapshot => $this->tracker->startStep($processId, $stepId));
    }

    public function completeStep(ProcessId|string $processId, StepId|string $stepId, ?OperationId $operationId = null): ProcessSnapshot
    {
        return $this->execute($operationId, 'complete_step', [(string) $processId, (string) $stepId],
            fn (): ProcessSnapshot => $this->tracker->completeStep($processId, $stepId));
    }

    public function failStep(ProcessId|string $processId, StepId|string $stepId, string $message, ?string $code = null, ?OperationId $operationId = null): ProcessSnapshot
    {
        return $this->execute($operationId, 'fail_step', [(string) $processId, (string) $stepId, $message, $code],
            fn (): ProcessSnapshot => $this->tracker->failStep($processId, $stepId, $message, $code));
    }

    public function skipStep(ProcessId|string $processId, StepId|string $stepId, ?OperationId $operationId = null): ProcessSnapshot
    {
        return $this->execute($operationId, 'skip_step', [(string) $processId, (string) $stepId],
            fn (): ProcessSnapshot => $this->tracker->skipStep($processId, $stepId));
    }

    public function complete(ProcessId|string $processId, ?OperationId $operationId = null): ProcessSnapshot
    {
        return $this->execute($operationId, 'complete', [(string) $processId],
            fn (): ProcessSnapshot => $this->tracker->complete($processId));
    }

    public function fail(ProcessId|string $processId, string $message, ?string $code = null, ?OperationId $operationId = null): ProcessSnapshot
    {
        return $this->execute($operationId, 'fail', [(string) $processId, $message, $code],
            fn (): ProcessSnapshot => $this->tracker->fail($processId, $message, $code));
    }

    public function cancel(ProcessId|string $processId, ?OperationId $operationId = null): ProcessSnapshot
    {
        return $this->execute($operationId, 'cancel', [(string) $processId],
            fn (): ProcessSnapshot => $this->tracker->cancel($processId));
    }

    private function execute(?OperationId $operationId, string $operation, array $arguments, callable $action): ProcessSnapshot
    {
        if ($operationId === null) {
            return $action();
        }

        $fingerprint = hash('sha256', serialize([$operation, $arguments]));
        $existing = $this->idempotency->get($operationId, $fingerprint);
        if ($existing !== null) {
            return $existing;
        }

        $result = $action();
        $this->idempotency->put($operationId, $fingerprint, $result);

        return $result;
    }
}
