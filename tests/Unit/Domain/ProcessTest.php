<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Tests\Unit\Domain;

use DateTimeImmutable;
use Oton\ProcessTracker\Domain\Failure;
use Oton\ProcessTracker\Domain\Process;
use Oton\ProcessTracker\Domain\ProcessId;
use Oton\ProcessTracker\Domain\ProcessStatus;
use Oton\ProcessTracker\Domain\ProcessType;
use Oton\ProcessTracker\Domain\Step;
use Oton\ProcessTracker\Domain\StepId;
use PHPUnit\Framework\TestCase;

final class ProcessTest extends TestCase
{
    public function testProcessCanBeCompletedAfterAllStepsComplete(): void
    {
        $now = new DateTimeImmutable('2026-01-01T10:00:00Z');
        $process = Process::start(new ProcessId('p-1'), new ProcessType('payment'), $now);
        $step = Step::pending(new StepId('provider'), 'provider');

        $process->addStep($step);
        $process->startStep(new StepId('provider'), $now);
        $process->completeStep(new StepId('provider'), $now);
        $process->complete($now);

        self::assertSame(ProcessStatus::Completed, $process->status());
    }

    public function testProcessCannotCompleteWithFailedStep(): void
    {
        $now = new DateTimeImmutable('2026-01-01T10:00:00Z');
        $process = Process::start(new ProcessId('p-1'), new ProcessType('payment'), $now);
        $step = Step::pending(new StepId('provider'), 'provider');

        $process->addStep($step);
        $process->startStep(new StepId('provider'), $now);
        $process->failStep(
            new StepId('provider'),
            new Failure('Provider timeout', $now, 'provider_timeout'),
        );

        $this->expectException(\LogicException::class);
        $process->complete($now);
    }

    public function testProcessCannotReceiveDuplicateStep(): void
    {
        $now = new DateTimeImmutable('2026-01-01T10:00:00Z');
        $process = Process::start(new ProcessId('p-1'), new ProcessType('payment'), $now);

        $process->addStep(Step::pending(new StepId('provider'), 'provider'));

        $this->expectException(\LogicException::class);
        $process->addStep(Step::pending(new StepId('provider'), 'provider'));
    }
}
