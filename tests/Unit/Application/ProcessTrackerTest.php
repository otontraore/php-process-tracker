<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Tests\Unit\Application;

use DateTimeImmutable;
use Oton\ProcessTracker\Application\ProcessTracker;
use Oton\ProcessTracker\Domain\Clock;
use Oton\ProcessTracker\Domain\InMemoryProcessRepository;
use Oton\ProcessTracker\Domain\ProcessId;
use PHPUnit\Framework\TestCase;

final class ProcessTrackerTest extends TestCase
{
    public function testTrackerProvidesSimpleLifecycleApi(): void
    {
        $clock = new class implements Clock {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-09-30T12:00:00Z');
            }
        };

        $tracker = new ProcessTracker(new InMemoryProcessRepository(), $clock);

        $tracker->start('PAY-1', 'payment');
        $tracker->addStep('PAY-1', 'provider', 'Provider');
        $tracker->addStep('PAY-1', 'ledger', 'Ledger');
        $tracker->startStep('PAY-1', 'provider');
        $tracker->completeStep('PAY-1', 'provider');
        $tracker->startStep('PAY-1', 'ledger');
        $tracker->completeStep('PAY-1', 'ledger');
        $snapshot = $tracker->complete('PAY-1');

        self::assertSame('PAY-1', $snapshot->id);
        self::assertSame('payment', $snapshot->type);
        self::assertSame('completed', $snapshot->status->value);
        self::assertCount(2, $snapshot->steps);
    }

    public function testStartingAnExistingProcessIsRejected(): void
    {
        $clock = new class implements Clock {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-09-30T12:00:00Z');
            }
        };

        $tracker = new ProcessTracker(new InMemoryProcessRepository(), $clock);
        $tracker->start('PAY-1', 'payment');

        $this->expectException(\LogicException::class);
        $tracker->start('PAY-1', 'payment');
    }

    public function testInspectionDoesNotModifyTheProcess(): void
    {
        $clock = new class implements Clock {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2026-09-30T12:00:00Z');
            }
        };

        $tracker = new ProcessTracker(new InMemoryProcessRepository(), $clock);
        $tracker->start('PAY-1', 'payment');
        $tracker->addStep('PAY-1', 'provider', 'Provider');

        $snapshot = $tracker->inspect('PAY-1');

        self::assertSame('pending', $snapshot->steps[0]->status->value);
        self::assertSame(0, $snapshot->steps[0]->attempt);
    }
}
