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
- start
- add/define steps
- start step
- complete step
- fail step
- cancel process
- complete process
- inspect current state
- invalid transition handling

## Phase 3 - Persistence
Status: implemented on dev.

- ProcessRepository and ProcessStore contracts
- transaction boundary contract
- in-memory implementations
- explicit process serialization contract
- database-neutral native serializer
- domain reconstitution with validation
- persistence independent from Laravel, Symfony and Doctrine

Acceptance criteria:
- persisted state can be serialized and reconstructed
- terminal processes can be reconstructed
- failures, attempts, timestamps and metadata survive round trips
- no persistence implementation requires a framework or database

## Phase 4 - Application services
Status: implemented on dev.

- ProcessManager
- ProcessInspector
- ProcessRetrier
- operation identifiers
- idempotent lifecycle operations through an explicit idempotency store
- correlation and causation identifiers
- lifecycle results represented by immutable snapshots
- in-memory idempotency implementation for tests

Acceptance criteria:
- repeated operations with the same operation ID return the original result
- reusing an operation ID with different parameters is rejected
- retry only starts failed steps
- inspection is read-only
- correlation and causation identifiers remain available without a tracing dependency

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

Do not start the next phase until the current phase passes its acceptance criteria on dev.
