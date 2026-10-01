<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Tests\Unit\Persistence;

use DateTimeImmutable;
use Oton\ProcessTracker\Domain\Failure;
use Oton\ProcessTracker\Domain\Process;
use Oton\ProcessTracker\Domain\ProcessId;
use Oton\ProcessTracker\Domain\ProcessStatus;
use Oton\ProcessTracker\Domain\ProcessType;
use Oton\ProcessTracker\Domain\Step;
use Oton\ProcessTracker\Domain\StepId;
use Oton\ProcessTracker\Domain\StepStatus;
use Oton\ProcessTracker\Persistence\InMemoryTransactionManager;
use Oton\ProcessTracker\Persistence\NativeProcessSerializer;
use PHPUnit\Framework\TestCase;

final class PersistenceTest extends TestCase
{
    public function testProcessRoundTripsThroughNativeSerializer(): void
    {
        $now = new DateTimeImmutable('2026-09-30T12:00:00Z');
        $process = Process::start(
            new ProcessId('p-1'),
            new ProcessType('payment'),
            $now,
            metadata: ['channel' => 'api'],
        );
        $process->addStep(Step::pending(new StepId('provider'), 'Provider'));
        $process->startStep(new StepId('provider'), $now);
        $process->failStep(new StepId('provider'), new Failure('Timeout', $now, 'timeout'));
        $process->fail(new Failure('Provider failed', $now, 'provider_failed'));

        $serializer = new NativeProcessSerializer();
        $copy = $serializer->deserialize($serializer->serialize($process));

        self::assertSame('p-1', (string) $copy->id());
        self::assertSame(ProcessStatus::Failed, $copy->status());
        self::assertSame(['channel' => 'api'], $copy->metadata());
        self::assertSame(StepStatus::Failed, $copy->step(new StepId('provider'))->status());
        self::assertSame(1, $copy->step(new StepId('provider'))->attempt());
    }

    public function testInMemoryTransactionReturnsOperationResult(): void
    {
        $manager = new InMemoryTransactionManager();
        self::assertSame(42, $manager->transaction(static fn (): int => 42));
    }
}
