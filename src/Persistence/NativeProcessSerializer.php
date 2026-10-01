<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Persistence;

use DateTimeImmutable;
use Oton\ProcessTracker\Domain\CausationId;
use Oton\ProcessTracker\Domain\CorrelationId;
use Oton\ProcessTracker\Domain\Failure;
use Oton\ProcessTracker\Domain\Process;
use Oton\ProcessTracker\Domain\ProcessId;
use Oton\ProcessTracker\Domain\ProcessStatus;
use Oton\ProcessTracker\Domain\ProcessType;
use Oton\ProcessTracker\Domain\Step;
use Oton\ProcessTracker\Domain\StepId;
use Oton\ProcessTracker\Domain\StepStatus;
use Oton\ProcessTracker\Domain\SubjectReference;

final class NativeProcessSerializer implements ProcessSerializer
{
    public function serialize(Process $process): array
    {
        return [
            'id' => (string) $process->id(),
            'type' => (string) $process->type(),
            'status' => $process->status()->value,
            'created_at' => $process->createdAt()->format(DATE_ATOM),
            'finished_at' => $process->finishedAt()?->format(DATE_ATOM),
            'failure' => $process->failure() === null ? null : [
                'message' => $process->failure()->message,
                'code' => $process->failure()->code,
                'occurred_at' => $process->failure()->occurredAt->format(DATE_ATOM),
            ],
            'subject' => $process->subject() === null ? null : [
                'type' => $process->subject()->type,
                'id' => $process->subject()->id,
            ],
            'metadata' => $process->metadata(),
            'correlation_id' => $process->correlationId()?->value,
            'causation_id' => $process->causationId()?->value,
            'steps' => array_map(
                static fn (Step $step): array => [
                    'id' => (string) $step->id(),
                    'name' => $step->name(),
                    'status' => $step->status()->value,
                    'started_at' => $step->startedAt()?->format(DATE_ATOM),
                    'finished_at' => $step->finishedAt()?->format(DATE_ATOM),
                    'failure' => $step->failure() === null ? null : [
                        'message' => $step->failure()->message,
                        'code' => $step->failure()->code,
                        'occurred_at' => $step->failure()->occurredAt->format(DATE_ATOM),
                    ],
                    'attempt' => $step->attempt(),
                ],
                $process->steps(),
            ),
        ];
    }

    public function deserialize(array $data): Process
    {
        $steps = array_map(function (array $item): Step {
            $failure = $item['failure'] ?? null;
            return Step::reconstitute(
                new StepId((string) $item['id']),
                (string) $item['name'],
                StepStatus::from((string) $item['status']),
                isset($item['started_at']) ? new DateTimeImmutable((string) $item['started_at']) : null,
                isset($item['finished_at']) ? new DateTimeImmutable((string) $item['finished_at']) : null,
                $failure === null ? null : new Failure(
                    (string) $failure['message'],
                    new DateTimeImmutable((string) $failure['occurred_at']),
                    isset($failure['code']) ? (string) $failure['code'] : null,
                ),
                (int) $item['attempt'],
            );
        }, $data['steps'] ?? []);

        $failure = $data['failure'] ?? null;
        $subject = $data['subject'] ?? null;

        return Process::reconstitute(
            new ProcessId((string) $data['id']),
            new ProcessType((string) $data['type']),
            ProcessStatus::from((string) $data['status']),
            new DateTimeImmutable((string) $data['created_at']),
            isset($data['finished_at']) ? new DateTimeImmutable((string) $data['finished_at']) : null,
            $failure === null ? null : new Failure(
                (string) $failure['message'],
                new DateTimeImmutable((string) $failure['occurred_at']),
                isset($failure['code']) ? (string) $failure['code'] : null,
            ),
            $subject === null ? null : new SubjectReference((string) $subject['type'], (string) $subject['id']),
            is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
            isset($data['correlation_id']) && $data['correlation_id'] !== null ? new CorrelationId((string) $data['correlation_id']) : null,
            isset($data['causation_id']) && $data['causation_id'] !== null ? new CausationId((string) $data['causation_id']) : null,
            $steps,
        );
    }
}
