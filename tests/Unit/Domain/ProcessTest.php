<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Tests\Unit\Domain;

use DateTimeImmutable;
use Oton\ProcessTracker\Domain\Process;
use Oton\ProcessTracker\Domain\ProcessId;
use Oton\ProcessTracker\Domain\ProcessStatus;
use Oton\ProcessTracker\Domain\Step;
use Oton\ProcessTracker\Domain\StepStatus;
use PHPUnit\Framework\TestCase;

final class ProcessTest extends TestCase
{
    public function testProcessCanBeCompletedAfterAllStepsComplete(): void
    {
        $now = new DateTimeImmutable('2026-01-01T10:00:00Z');
        $process = Process::start(new ProcessId('p-1'), 'payment', $now);
        $step = Step::pending('provider');

        $process->addStep($step);
        $step->start($now);
        $step->complete($now);

        $process->complete($now);

        self::assertSame(ProcessStatus::Completed, $process->status());
    }

    public function testProcessCannotCompleteWithFailedStep(): void
    {
        $now = new DateTimeImmutable('2026-01-01T10:00:00Z');
        $process = Process::start(new ProcessId('p-1'), 'payment', $now);
        $step = Step::pending('provider');

        $process->addStep($step);
        $step->start($now);
        $step->fail('Provider timeout', $now);

        $this->expectException(\LogicException::class);
        $process->complete($now);
    }

    public function testProcessCannotReceiveDuplicateStep(): void
    {
        $now = new DateTimeImmutable('2026-01-01T10:00:00Z');
        $process = Process::start(new ProcessId('p-1'), 'payment', $now);

        $process->addStep(Step::pending('provider'));

        $this->expectException(\LogicException::class);
        $process->addStep(Step::pending('provider'));
    }
}
