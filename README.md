# PHP Process Tracker

Track multi-step business processes in PHP applications.

PHP Process Tracker provides a small, framework-agnostic core for tracking business processes, their steps, failures and lifecycle. Laravel and Symfony integrations are built on top of the same core contracts.

## What it solves

Use it when an operation has several meaningful business steps and you need to know:
- where the process currently is
- which steps completed or failed
- how many times a step was attempted
- why a process or step failed
- whether the process can be completed
- how to inspect the current state from application code

Typical use cases include payments, orders, refunds, KYC, imports, exports, synchronization and notification workflows.

It is not a replacement for a queue system, application logger or distributed tracing system.

## Requirements

- PHP 8.2 or newer

Framework integrations are intentionally separate from the core.

## Installation

    composer require otontraore/process-tracker

## Plain PHP quick start

```php
use Oton\ProcessTracker\Application\ProcessTracker;
use Oton\ProcessTracker\Domain\InMemoryProcessRepository;
use Oton\ProcessTracker\Domain\SystemClock;

$tracker = new ProcessTracker(new InMemoryProcessRepository(), new SystemClock());

$tracker->start('PAY-82931', 'payment');
$tracker->addStep('PAY-82931', 'provider', 'Provider authorization');
$tracker->addStep('PAY-82931', 'ledger', 'Ledger entry');

$tracker->startStep('PAY-82931', 'provider');
$tracker->completeStep('PAY-82931', 'provider');
$tracker->startStep('PAY-82931', 'ledger');
$tracker->completeStep('PAY-82931', 'ledger');

$process = $tracker->complete('PAY-82931');
```

## Lifecycle

```text
running
   |
   +--> completed
   +--> failed
   +--> cancelled
```

Steps:

```text
pending --> running --> completed
                    \-> failed --> running
pending ------------> skipped
```

Transitions are validated by the domain model. Invalid lifecycle operations raise explicit exceptions instead of silently changing state.

## Application API

The application service exposes a deliberately small API:

- `start()`
- `addStep()`
- `startStep()`
- `completeStep()`
- `failStep()`
- `skipStep()`
- `complete()`
- `fail()`
- `cancel()`
- `inspect()`

Persistence is provided through `ProcessRepository`. The core ships with an in-memory repository for development and tests.

## Architecture

```text
Application API
      |
      v
Domain model
      |
      v
Repository contract
      |
      +--> In-memory
      +--> Laravel / Eloquent
      +--> Symfony / Doctrine
```

The core does not depend on Laravel, Symfony, Doctrine, a database or a queue implementation.

## Documentation

- [Architecture](docs/ARCHITECTURE.md)
- [Roadmap](docs/ROADMAP.md)
- [Contributing](CONTRIBUTING.md)

## Development

    composer install
    composer test
    composer analyse

The CI matrix verifies supported PHP versions on every push to the development branches.

## Status

The project is under active development. The public API will be treated as unstable until the first stable 1.0 release.
