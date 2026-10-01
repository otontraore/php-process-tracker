<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Tests\Unit\Application;

use DateTimeImmutable;
use Oton\ProcessTracker\Domain\Clock;
use Oton\ProcessTracker\Domain\InMemoryProcessRepository;
use Oton\ProcessTracker\Domain\StepStatus;
use Oton\ProcessTracker\Application\InMemoryIdempotencyStore;
use Oton\ProcessTracker\Application\OperationId;
use Oton\ProcessTracker\Application\ProcessInspector;
use Oton\ProcessTracker\Application\ProcessManager;
use Oton\ProcessTracker\Application\ProcessRetrier;
use Oton\ProcessTracker\Application\ProcessTracker;
use PHPUnit\Framework\TestCase;

final class ProcessManagerTest extends TestCase
{
    private function manager(): ProcessManager
    {
        $clock = new class implements Clock {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-09-30T12:00:00Z');
            }
        };

        return new ProcessManager(
            new ProcessTracker(new InMemoryProcessRepository(), $clock),
            new InMemoryIdempotencyStore(),
        );
    }

    public function testSameOperationIdReturnsTheOriginalResult(): void
    {
        $manager = $this->manager();
        $first = $manager->start('PAY-1', 'payment', operationId: new OperationId('op-1'));
        $second = $manager->start('PAY-1', 'payment', operationId: new OperationId('op-1'));

        self::assertSame($first, $second);
    }

    public function testReusingOperationIdWithDifferentArgumentsIsRejected(): void
    {
        $manager = $this->manager();
        $operation = new OperationId('op-1');
        $manager->start('PAY-1', 'payment', operationId: $operation);

        $this->expectException(\LogicException::class);
        $manager->start('PAY-2', 'payment', operationId: $operation);
    }

    public function testInspectorReportsTerminalState(): void
    {
        $tracker = new ProcessTracker(
            new InMemoryProcessRepository(),
            new class implements Clock {
                public function now(): DateTimeImmutable { return new DateTimeImmutable('2026-09-30T12:00:00Z'); }
            },
        );
        $tracker->start('PAY-1', 'payment');
        $tracker->addStep('PAY-1', 'provider', 'Provider');
        $tracker->startStep('PAY-1', 'provider');
        $tracker->completeStep('PAY-1', 'provider');
        $tracker->complete('PAY-1');

        $inspector = new ProcessInspector($tracker);
        self::assertTrue($inspector->isTerminal('PAY-1'));
        self::assertFalse($inspector->isFailed('PAY-1'));
    }

    public function testRetrierStartsAFailedStep(): void
    {
        $tracker = new ProcessTracker(
            new InMemoryProcessRepository(),
            new class implements Clock {
                public function now(): DateTimeImmutable { return new DateTimeImmutable('2026-09-30T12:00:00Z'); }
            },
        );
        $tracker->start('PAY-1', 'payment');
        $tracker->addStep('PAY-1', 'provider', 'Provider');
        $tracker->startStep('PAY-1', 'provider');
        $tracker->failStep('PAY-1', 'provider', 'Timeout');

        $snapshot = (new ProcessRetrier($tracker))->retryStep('PAY-1', 'provider');

        self::assertSame(StepStatus::Running, $snapshot->steps[0]->status);
        self::assertSame(2, $snapshot->steps[0]->attempt);
    }
}
