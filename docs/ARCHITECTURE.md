# Architecture

## Layers

1. Domain
   - Process identity
   - Process state
   - Step identity and state
   - timestamps
   - metadata
   - failure information
   - correlation and causation identifiers
2. Application
   - ProcessTracker lifecycle operations
   - ProcessManager orchestration and idempotency
   - ProcessInspector read operations
   - ProcessRetrier explicit retry
   - immutable snapshots
3. Persistence
   - ProcessRepository and ProcessStore contracts
   - transaction boundary contract
   - serialization contract
   - database-neutral serializer
   - framework/database adapters added later
4. Integrations
   - Laravel service provider, facade/helpers, migrations and queue integration
   - Symfony bundle, dependency injection, console integration and Messenger integration
5. Optional presentation
   - CLI and later dashboard/API tooling

## Core principles

- The core must work in plain PHP.
- A process is not a queue job.
- A process can contain synchronous and asynchronous steps.
- A failed step must remain inspectable.
- Retry is explicit.
- Process state can be serialized and reconstituted without a framework.
- Storage implementations must be replaceable.
- Correlation and causation identifiers are framework-neutral.
- Idempotency is explicit and requires an operation identifier.
- Read operations do not mutate process state.

## Persistence boundary

The domain does not know how a process is stored.

ProcessStore defines the minimum storage contract. ProcessRepository extends that contract for backward-compatible application code.

ProcessSerializer converts the domain model to a database-neutral array representation and reconstructs it with explicit validation. Laravel and Symfony adapters can map that representation to their own persistence models without changing the domain.

TransactionManager represents a transaction boundary but does not require a particular database engine.

## Application services

ProcessTracker is the low-level lifecycle API.

ProcessManager is the application-facing orchestration service. It can apply idempotency when a caller supplies an OperationId.

ProcessInspector exposes read-only inspection helpers.

ProcessRetrier performs explicit retry of failed steps. Retry policy, backoff and maximum-attempt rules are intentionally deferred to the failure/retry phase.

## Initial domain model

Process:
- id
- type
- subject reference
- status
- timestamps
- metadata
- failure summary
- correlation ID
- causation ID
- steps

Step:
- id
- name
- status
- timestamps
- attempt
- failure summary

Statuses are explicit enums and transitions are validated.

## Out of scope for the first stable core

- graphical dashboard
- distributed tracing vendor lock-in
- automatic retries without an explicit policy
- workflow DSL
- persistence tied directly to Eloquent or Doctrine
- implicit idempotency based on timestamps or process state
