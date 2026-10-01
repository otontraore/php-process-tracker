<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Application;

interface IdempotencyStore
{
    public function get(OperationId $id, string $fingerprint): ?ProcessSnapshot;
    public function put(OperationId $id, string $fingerprint, ProcessSnapshot $result): void;
}
