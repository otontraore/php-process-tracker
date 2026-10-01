<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Application;

final class InMemoryIdempotencyStore implements IdempotencyStore
{
    /** @var array<string, IdempotencyRecord> */
    private array $records = [];

    public function get(OperationId $id, string $fingerprint): ?ProcessSnapshot
    {
        $record = $this->records[(string) $id] ?? null;
        if ($record === null) {
            return null;
        }

        if ($record->fingerprint !== $fingerprint) {
            throw new \LogicException(sprintf('Operation "%s" was already used with different parameters.', $id));
        }

        return $record->result;
    }

    public function put(OperationId $id, string $fingerprint, ProcessSnapshot $result): void
    {
        $key = (string) $id;
        $existing = $this->records[$key] ?? null;

        if ($existing !== null && $existing->fingerprint !== $fingerprint) {
            throw new \LogicException(sprintf('Operation "%s" was already used with different parameters.', $id));
        }

        $this->records[$key] = new IdempotencyRecord($fingerprint, $result);
    }
}
