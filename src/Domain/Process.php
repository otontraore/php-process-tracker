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
        private readonly string $type,
        private ProcessStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $completedAt = null,
        private ?string $failureMessage = null,
    ) {
    }

    public static function start(ProcessId $id, string $type, DateTimeImmutable $now): self
    {
        if ($type === '') {
            throw new \InvalidArgumentException('Process type cannot be empty.');
        }

        return new self($id, $type, ProcessStatus::Running, $now);
    }

    public function id(): ProcessId
    {
        return $this->id;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function status(): ProcessStatus
    {
        return $this->status;
    }

    public function addStep(Step $step): void
    {
        if ($this->status->isTerminal()) {
            throw new \LogicException('A terminal process cannot receive new steps.');
        }

        if (isset($this->steps[$step->name()])) {
            throw new \LogicException(sprintf('Step "%s" already exists.', $step->name()));
        }

        $this->steps[$step->name()] = $step;
    }

    public function step(string $name): Step
    {
        return $this->steps[$name] ?? throw new \OutOfBoundsException(sprintf('Step "%s" does not exist.', $name));
    }

    /** @return list<Step> */
    public function steps(): array
    {
        return array_values($this->steps);
    }

    public function complete(DateTimeImmutable $now): void
    {
        if ($this->status !== ProcessStatus::Running) {
            throw new \LogicException('Only running processes can complete.');
        }

        foreach ($this->steps as $step) {
            if (!$step->status()->isTerminal() || $step->status() === StepStatus::Failed) {
                throw new \LogicException('A process can only complete when every step is completed or skipped.');
            }
        }

        $this->status = ProcessStatus::Completed;
        $this->completedAt = $now;
    }

    public function fail(string $message, DateTimeImmutable $now): void
    {
        if ($this->status !== ProcessStatus::Running) {
            throw new \LogicException('Only running processes can fail.');
        }

        $this->status = ProcessStatus::Failed;
        $this->completedAt = $now;
        $this->failureMessage = $message;
    }

    public function cancel(DateTimeImmutable $now): void
    {
        if ($this->status->isTerminal()) {
            throw new \LogicException('A terminal process cannot be cancelled.');
        }

        $this->status = ProcessStatus::Cancelled;
        $this->completedAt = $now;
    }

    public function failureMessage(): ?string
    {
        return $this->failureMessage;
    }
}
