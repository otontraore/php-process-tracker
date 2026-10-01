<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Application;

use Oton\ProcessTracker\Domain\CausationId;
use Oton\ProcessTracker\Domain\Clock;
use Oton\ProcessTracker\Domain\CorrelationId;
use Oton\ProcessTracker\Domain\Failure;
use Oton\ProcessTracker\Domain\Process;
use Oton\ProcessTracker\Domain\ProcessId;
use Oton\ProcessTracker\Domain\ProcessRepository;
use Oton\ProcessTracker\Domain\ProcessType;
use Oton\ProcessTracker\Domain\Step;
use Oton\ProcessTracker\Domain\StepId;
use Oton\ProcessTracker\Domain\SubjectReference;

final class ProcessTracker
{
    public function __construct(
        private readonly ProcessRepository $repository,
        private readonly Clock $clock,
    ) {}

    /** @param array<string, mixed> $metadata */
    public function start(
        ProcessId|string $id,
        ProcessType|string $type,
        ?SubjectReference $subject = null,
        array $metadata = [],
        CorrelationId|string|null $correlationId = null,
        CausationId|string|null $causationId = null,
    ): ProcessSnapshot {
        $processId = $id instanceof ProcessId ? $id : new ProcessId($id);
        if ($this->repository->has($processId)) {
            throw new \LogicException(sprintf('Process "%s" already exists.', $processId));
        }

        $process = Process::start(
            $processId,
            $type instanceof ProcessType ? $type : new ProcessType($type),
            $this->clock->now(),
            $subject,
            $metadata,
            $correlationId instanceof CorrelationId || $correlationId === null
                ? $correlationId
                : new CorrelationId($correlationId),
            $causationId instanceof CausationId || $causationId === null
                ? $causationId
                : new CausationId($causationId),
        );
        $this->repository->save($process);
        return $this->snapshot($process);
    }

    public function addStep(ProcessId|string $processId, StepId|string $stepId, string $name): ProcessSnapshot
    {
        $process = $this->getProcess($processId);
        $process->addStep(Step::pending(
            $stepId instanceof StepId ? $stepId : new StepId($stepId), $name
        ));
        $this->repository->save($process);
        return $this->snapshot($process);
    }

    public function startStep(ProcessId|string $processId, StepId|string $stepId): ProcessSnapshot
    {
        $process = $this->getProcess($processId);
        $process->startStep($stepId instanceof StepId ? $stepId : new StepId($stepId), $this->clock->now());
        $this->repository->save($process);
        return $this->snapshot($process);
    }

    public function completeStep(ProcessId|string $processId, StepId|string $stepId): ProcessSnapshot
    {
        $process = $this->getProcess($processId);
        $process->completeStep($stepId instanceof StepId ? $stepId : new StepId($stepId), $this->clock->now());
        $this->repository->save($process);
        return $this->snapshot($process);
    }

    public function failStep(ProcessId|string $processId, StepId|string $stepId, string $message, ?string $code = null): ProcessSnapshot
    {
        $process = $this->getProcess($processId);
        $process->failStep(
            $stepId instanceof StepId ? $stepId : new StepId($stepId),
            new Failure($message, $this->clock->now(), $code),
        );
        $this->repository->save($process);
        return $this->snapshot($process);
    }

    public function skipStep(ProcessId|string $processId, StepId|string $stepId): ProcessSnapshot
    {
        $process = $this->getProcess($processId);
        $process->skipStep($stepId instanceof StepId ? $stepId : new StepId($stepId), $this->clock->now());
        $this->repository->save($process);
        return $this->snapshot($process);
    }

    public function complete(ProcessId|string $processId): ProcessSnapshot
    {
        $process = $this->getProcess($processId);
        $process->complete($this->clock->now());
        $this->repository->save($process);
        return $this->snapshot($process);
    }

    public function fail(ProcessId|string $processId, string $message, ?string $code = null): ProcessSnapshot
    {
        $process = $this->getProcess($processId);
        $process->fail(new Failure($message, $this->clock->now(), $code));
        $this->repository->save($process);
        return $this->snapshot($process);
    }

    public function cancel(ProcessId|string $processId): ProcessSnapshot
    {
        $process = $this->getProcess($processId);
        $process->cancel($this->clock->now());
        $this->repository->save($process);
        return $this->snapshot($process);
    }

    public function inspect(ProcessId|string $processId): ProcessSnapshot
    {
        return $this->snapshot($this->getProcess($processId));
    }

    private function getProcess(ProcessId|string $id): Process
    {
        return $this->repository->get($id instanceof ProcessId ? $id : new ProcessId($id));
    }

    private function snapshot(Process $process): ProcessSnapshot
    {
        $steps = array_map(
            static fn (Step $step): StepSnapshot => new StepSnapshot(
                (string) $step->id(), $step->name(), $step->status(), $step->attempt(),
                $step->startedAt(), $step->finishedAt(),
                $step->failure()?->message, $step->failure()?->code,
            ),
            $process->steps(),
        );

        return new ProcessSnapshot(
            (string) $process->id(), (string) $process->type(), $process->status(),
            $process->createdAt(), $process->finishedAt(), $process->failure()?->message,
            $process->subject()?->type, $process->subject()?->id, $process->metadata(), $steps,
            $process->correlationId()?->value, $process->causationId()?->value,
        );
    }
}
