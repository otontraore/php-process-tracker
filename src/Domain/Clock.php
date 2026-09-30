<?php

declare(strict_types=1);

namespace Oton\ProcessTracker\Domain;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
