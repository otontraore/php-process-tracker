<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Domain;

use DateTimeImmutable;

final class Step
{
    private function __construct(
        private readonly string $name,
        private StepStatus $status,
        private ?DateTimeImmutable $startedAt = null,
        private ?DateTimeImmutable $completedAt = null,
        private ?string $failureMessage = null,
        private int $attempt = 0,
    ) {
    }

    public static function pending(string $name): self
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Step name cannot be empty.');
        }

        return new self($name, StepStatus::Pending);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function status(): StepStatus
    {
        return $this->status;
    }

    public function attempt(): int
    {
        return $this->attempt;
    }

    public function start(DateTimeImmutable $now): void
    {
        if ($this->status !== StepStatus::Pending && $this->status !== StepStatus::Failed) {
            throw new \LogicException('Only pending or failed steps can start.');
        }

        $this->status = StepStatus::Running;
        $this->startedAt = $now;
        $this->completedAt = null;
        $this->failureMessage = null;
        $this->attempt++;
    }

    public function complete(DateTimeImmutable $now): void
    {
        if ($this->status !== StepStatus::Running) {
            throw new \LogicException('Only running steps can complete.');
        }

        $this->status = StepStatus::Completed;
        $this->completedAt = $now;
    }

    public function fail(string $message, DateTimeImmutable $now): void
    {
        if ($this->status !== StepStatus::Running) {
            throw new \LogicException('Only running steps can fail.');
        }

        $this->status = StepStatus::Failed;
        $this->completedAt = $now;
        $this->failureMessage = $message;
    }

    public function failureMessage(): ?string
    {
        return $this->failureMessage;
    }
}
