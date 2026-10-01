<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Domain;

use DateTimeImmutable;

final class Process
{
    /** @var array<string, Step> */
    private array $steps = [];

    private function __construct(
        private readonly ProcessId $id,
        private readonly ProcessType $type,
        private ProcessStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $finishedAt = null,
        private ?Failure $failure = null,
        private readonly ?SubjectReference $subject = null,
        private array $metadata = [],
        private readonly ?CorrelationId $correlationId = null,
        private readonly ?CausationId $causationId = null,
    ) {
    }

    public static function start(
        ProcessId $id,
        ProcessType $type,
        DateTimeImmutable $now,
        ?SubjectReference $subject = null,
        array $metadata = [],
        ?CorrelationId $correlationId = null,
        ?CausationId $causationId = null,
    ): self {
        return new self(
            $id, $type, ProcessStatus::Running, $now,
            subject: $subject, metadata: $metadata,
            correlationId: $correlationId, causationId: $causationId,
        );
    }

    /**
     * @param list<Step> $steps
     * @param array<string, mixed> $metadata
     */
    public static function reconstitute(
        ProcessId $id,
        ProcessType $type,
        ProcessStatus $status,
        DateTimeImmutable $createdAt,
        ?DateTimeImmutable $finishedAt,
        ?Failure $failure,
        ?SubjectReference $subject,
        array $metadata,
        ?CorrelationId $correlationId,
        ?CausationId $causationId,
        array $steps,
    ): self {
        if ($status === ProcessStatus::Running && $finishedAt !== null) {
            throw new \InvalidArgumentException('A running process cannot have a finished timestamp.');
        }
        if ($status->isTerminal() && $finishedAt === null) {
            throw new \InvalidArgumentException('A terminal process must have a finished timestamp.');
        }

        $process = new self(
            $id, $type, $status, $createdAt, $finishedAt, $failure, $subject,
            $metadata, $correlationId, $causationId,
        );

        foreach ($steps as $step) {
            if (!$step instanceof Step) {
                throw new \InvalidArgumentException('Process steps must contain only Step instances.');
            }
            $process->addStep($step);
        }

        return $process;
    }

    public function id(): ProcessId { return $this->id; }
    public function type(): ProcessType { return $this->type; }
    public function status(): ProcessStatus { return $this->status; }
    public function createdAt(): DateTimeImmutable { return $this->createdAt; }
    public function finishedAt(): ?DateTimeImmutable { return $this->finishedAt; }
    public function subject(): ?SubjectReference { return $this->subject; }
    public function metadata(): array { return $this->metadata; }
    public function failure(): ?Failure { return $this->failure; }
    public function correlationId(): ?CorrelationId { return $this->correlationId; }
    public function causationId(): ?CausationId { return $this->causationId; }

    public function addStep(Step $step): void
    {
        if ($this->status->isTerminal()) {
            throw new \LogicException('A terminal process cannot receive new steps.');
        }

        $key = (string) $step->id();
        if (isset($this->steps[$key])) {
            throw new \LogicException(sprintf('Step "%s" already exists.', $step->id()));
        }

        $this->steps[$key] = $step;
    }

    public function step(StepId $id): Step
    {
        return $this->steps[(string) $id]
            ?? throw new \OutOfBoundsException(sprintf('Step "%s" does not exist.', $id));
    }

    /** @return list<Step> */
    public function steps(): array
    {
        return array_values($this->steps);
    }

    public function startStep(StepId $id, DateTimeImmutable $now): void
    {
        $this->assertRunning();
        $this->step($id)->start($now);
    }

    public function completeStep(StepId $id, DateTimeImmutable $now): void
    {
        $this->assertRunning();
        $this->step($id)->complete($now);
    }

    public function failStep(StepId $id, Failure $failure): void
    {
        $this->assertRunning();
        $this->step($id)->fail($failure);
    }

    public function skipStep(StepId $id, DateTimeImmutable $now): void
    {
        $this->assertRunning();
        $this->step($id)->skip($now);
    }

    public function complete(DateTimeImmutable $now): void
    {
        $this->assertRunning();

        if ($this->steps === []) {
            throw new \LogicException('A process must contain at least one step before completion.');
        }

        foreach ($this->steps as $step) {
            if ($step->status() !== StepStatus::Completed && $step->status() !== StepStatus::Skipped) {
                throw new \LogicException('A process can only complete when every step is completed or skipped.');
            }
        }

        $this->status = ProcessStatus::Completed;
        $this->finishedAt = $now;
    }

    public function fail(Failure $failure): void
    {
        $this->assertRunning();
        $this->status = ProcessStatus::Failed;
        $this->finishedAt = $failure->occurredAt;
        $this->failure = $failure;
    }

    public function cancel(DateTimeImmutable $now): void
    {
        if ($this->status->isTerminal()) {
            throw new \LogicException('A terminal process cannot be cancelled.');
        }

        $this->status = ProcessStatus::Cancelled;
        $this->finishedAt = $now;
    }

    private function assertRunning(): void
    {
        if ($this->status !== ProcessStatus::Running) {
            throw new \LogicException('Only running processes can be modified.');
        }
    }
}
