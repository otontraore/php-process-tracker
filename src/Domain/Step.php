<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Domain;

use DateTimeImmutable;

final class Step
{
    private function __construct(
        private readonly StepId $id,
        private readonly string $name,
        private StepStatus $status,
        private ?DateTimeImmutable $startedAt = null,
        private ?DateTimeImmutable $finishedAt = null,
        private ?Failure $failure = null,
        private int $attempt = 0,
    ) {
    }

    public static function pending(StepId $id, string $name): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Step name cannot be empty.');
        }

        return new self($id, $name, StepStatus::Pending);
    }

    public static function reconstitute(
        StepId $id,
        string $name,
        StepStatus $status,
        ?DateTimeImmutable $startedAt,
        ?DateTimeImmutable $finishedAt,
        ?Failure $failure,
        int $attempt,
    ): self {
        if ($name === '') {
            throw new \InvalidArgumentException('Step name cannot be empty.');
        }
        if ($attempt < 0) {
            throw new \InvalidArgumentException('Step attempt cannot be negative.');
        }
        if ($status === StepStatus::Running && ($finishedAt !== null || $failure !== null)) {
            throw new \InvalidArgumentException('A running step cannot have a finish timestamp or failure.');
        }
        if ($status === StepStatus::Failed && $failure === null) {
            throw new \InvalidArgumentException('A failed step must have failure information.');
        }
        if ($status->isTerminal() && $finishedAt === null) {
            throw new \InvalidArgumentException('A terminal step must have a finished timestamp.');
        }

        return new self($id, $name, $status, $startedAt, $finishedAt, $failure, $attempt);
    }

    public function id(): StepId { return $this->id; }
    public function name(): string { return $this->name; }
    public function status(): StepStatus { return $this->status; }
    public function attempt(): int { return $this->attempt; }
    public function startedAt(): ?DateTimeImmutable { return $this->startedAt; }
    public function finishedAt(): ?DateTimeImmutable { return $this->finishedAt; }
    public function failure(): ?Failure { return $this->failure; }

    public function start(DateTimeImmutable $now): void
    {
        if ($this->status !== StepStatus::Pending && $this->status !== StepStatus::Failed) {
            throw new \LogicException('Only pending or failed steps can start.');
        }

        $this->status = StepStatus::Running;
        $this->startedAt = $now;
        $this->finishedAt = null;
        $this->failure = null;
        $this->attempt++;
    }

    public function complete(DateTimeImmutable $now): void
    {
        if ($this->status !== StepStatus::Running) {
            throw new \LogicException('Only running steps can complete.');
        }

        $this->status = StepStatus::Completed;
        $this->finishedAt = $now;
    }

    public function fail(Failure $failure): void
    {
        if ($this->status !== StepStatus::Running) {
            throw new \LogicException('Only running steps can fail.');
        }

        $this->status = StepStatus::Failed;
        $this->finishedAt = $failure->occurredAt;
        $this->failure = $failure;
    }

    public function skip(DateTimeImmutable $now): void
    {
        if ($this->status !== StepStatus::Pending) {
            throw new \LogicException('Only pending steps can be skipped.');
        }

        $this->status = StepStatus::Skipped;
        $this->finishedAt = $now;
    }
}
