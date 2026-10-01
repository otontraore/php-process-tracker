<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Persistence;

final class InMemoryTransactionManager implements TransactionManager
{
    public function transaction(callable $operation): mixed
    {
        return $operation();
    }
}
