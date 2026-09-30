<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Domain;

use DateTimeImmutable;

final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
