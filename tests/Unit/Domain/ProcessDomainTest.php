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
use Oton\ProcessTracker\Domain\StepStatus;
use Oton\ProcessTracker\Domain\SubjectReference;
use PHPUnit\Framework\TestCase;

final class ProcessDomainTest extends TestCase
{
    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-30T12:00:00Z');
    }

    private function process(): Process
    {
        return Process::start(new ProcessId('p-1'), new ProcessType('payment'), $this->now());
    }

    public function testProcessStartsRunningWithSubjectAndMetadata(): void
    {
        $process = Process::start(
            new ProcessId('p-1'), new ProcessType('payment'), $this->now(),
            new SubjectReference('payment', 'pay-1'), ['channel' => 'api'],
        );

        self::assertSame(ProcessStatus::Running, $process->status());
        self::assertSame('pay-1', $process->subject()?->id);
        self::assertSame(['channel' => 'api'], $process->metadata());
    }

    public function testStepLifecycleSupportsCompleteAndSkip(): void
    {
        $process = $this->process();
        $payment = Step::pending(new StepId('payment'), 'payment');
        $email = Step::pending(new StepId('email'), 'email');
        $process->addStep($payment);
        $process->addStep($email);

        $process->startStep(new StepId('payment'), $this->now());
        $process->completeStep(new StepId('payment'), $this->now());
        $process->skipStep(new StepId('email'), $this->now());
        $process->complete($this->now());

        self::assertSame(ProcessStatus::Completed, $process->status());
        self::assertSame(StepStatus::Completed, $payment->status());
        self::assertSame(StepStatus::Skipped, $email->status());
    }

    public function testFailedStepPreventsProcessCompletion(): void
    {
        $process = $this->process();
        $process->addStep(Step::pending(new StepId('provider'), 'provider'));
        $process->startStep(new StepId('provider'), $this->now());
        $process->failStep(new StepId('provider'), new Failure('Provider timeout', $this->now(), 'provider_timeout'));

        $this->expectException(\LogicException::class);
        $process->complete($this->now());
    }

    public function testFailedStepCanBeRetriedByStartingItAgain(): void
    {
        $process = $this->process();
        $step = Step::pending(new StepId('provider'), 'provider');
        $process->addStep($step);
        $process->startStep(new StepId('provider'), $this->now());
        $process->failStep(new StepId('provider'), new Failure('Timeout', $this->now()));
        $process->startStep(new StepId('provider'), $this->now());

        self::assertSame(StepStatus::Running, $step->status());
        self::assertSame(2, $step->attempt());
        self::assertNull($step->failure());
    }

    public function testCompletedStepCannotBeStartedAgain(): void
    {
        $process = $this->process();
        $process->addStep(Step::pending(new StepId('provider'), 'provider'));
        $process->startStep(new StepId('provider'), $this->now());
        $process->completeStep(new StepId('provider'), $this->now());

        $this->expectException(\LogicException::class);
        $process->startStep(new StepId('provider'), $this->now());
    }

    public function testDuplicateStepIdIsRejected(): void
    {
        $process = $this->process();
        $process->addStep(Step::pending(new StepId('provider'), 'provider'));

        $this->expectException(\LogicException::class);
        $process->addStep(Step::pending(new StepId('provider'), 'another'));
    }

    public function testProcessCannotBeCompletedWhileAStepIsPending(): void
    {
        $process = $this->process();
        $process->addStep(Step::pending(new StepId('provider'), 'provider'));

        $this->expectException(\LogicException::class);
        $process->complete($this->now());
    }

    public function testProcessCannotBeCompletedWithoutSteps(): void
    {
        $this->expectException(\LogicException::class);
        $this->process()->complete($this->now());
    }

    public function testTerminalProcessCannotBeModified(): void
    {
        $process = $this->process();
        $process->addStep(Step::pending(new StepId('provider'), 'provider'));
        $process->startStep(new StepId('provider'), $this->now());
        $process->completeStep(new StepId('provider'), $this->now());
        $process->complete($this->now());

        $this->expectException(\LogicException::class);
        $process->addStep(Step::pending(new StepId('late'), 'late'));
    }

    public function testStepIdentityAndFailureArePreserved(): void
    {
        $step = Step::pending(new StepId('provider'), 'Provider');
        $failure = new Failure('Timeout', $this->now(), 'timeout');

        $step->start($this->now());
        $step->fail($failure);

        self::assertSame('provider', (string) $step->id());
        self::assertSame('Provider', $step->name());
        self::assertSame($failure, $step->failure());
        self::assertSame($this->now(), $step->finishedAt());
    }
}
