<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Persistence;

interface TransactionManager
{
    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     */
    public function transaction(callable $operation): mixed;
}
