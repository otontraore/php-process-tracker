# Roadmap

## Phase 0 - Repository foundation
- Composer package
- PSR-4 autoloading
- PHPUnit
- PHPStan
- CI
- coding conventions
- documentation skeleton
- branch/release workflow

## Phase 1 - Domain foundation
- ProcessId and StepId
- ProcessType
- SubjectReference
- ProcessStatus
- StepStatus
- Process
- Step
- Clock abstraction
- metadata contract
- failure representation
- strict transition rules
- exhaustive unit tests

## Phase 2 - Process lifecycle
Implement and test:
- start
- add/define steps
- start step
- complete step
- fail step
- cancel process
- complete process
- resume process
- inspect current state
- invalid transition handling

## Phase 3 - Persistence
- ProcessRepository contract
- ProcessStore contract
- transactional operation contract
- in-memory implementation for tests
- serialization contract
- database-neutral persistence rules

## Phase 4 - Application services
- ProcessManager
- ProcessInspector
- ProcessRetrier
- lifecycle commands/results
- idempotent operations
- correlation and causation identifiers

## Phase 5 - Laravel integration
- service provider
- configuration publishing
- facade/API
- Eloquent persistence
- migrations
- Artisan inspection commands
- queue/job correlation
- Laravel events
- Laravel tests on supported versions

## Phase 6 - Symfony integration
- Bundle
- configuration
- Doctrine persistence
- Console commands
- EventDispatcher integration
- Messenger integration
- Symfony tests on supported versions

## Phase 7 - Failure and retry model
- retry policy interface
- attempt records
- exponential/backoff policy
- maximum attempts
- manual retry
- retry safety
- stuck-process detection
- compensation hooks without forcing a saga implementation

## Phase 8 - Observability
- structured process events
- process timeline
- correlation/causation
- logging adapter
- metrics adapter
- OpenTelemetry-compatible extension points without a hard dependency

## Phase 9 - Developer experience
- excellent README
- quick start for Laravel
- quick start for Symfony
- plain PHP example
- payment/order/KYC examples
- troubleshooting
- upgrade guide
- API documentation
- package compatibility matrix

## Phase 10 - Hardening and 1.0
- mutation testing on critical transitions
- concurrency tests
- serialization tests
- performance benchmarks
- security review
- BC review
- release checklist
- v1.0.0 tag

## Development rule

Do not start the next phase until the current phase passes its acceptance criteria on `dev`.
