# Architecture

## Layers

1. Domain
   - Process identity
   - Process state
   - Step identity and state
   - timestamps
   - metadata
   - failure information
2. Application
   - start, resume, complete, fail, cancel, retry and inspect use cases
3. Persistence
   - repository contracts
   - transaction boundary contracts
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
- Retry must be explicit and idempotent.
- Process history must not depend on a specific database.
- Storage implementations must be replaceable.
- Correlation and causation identifiers must be supported without coupling to a tracing vendor.

## Initial domain model

Process:
- id
- type
- subject reference
- status
- timestamps
- metadata
- current step
- failure summary

Step:
- name
- status
- timestamps
- attempt
- metadata
- failure summary

Statuses are explicit value objects/enums and transitions are validated.

## Out of scope for the first stable core

- graphical dashboard
- distributed tracing vendor lock-in
- automatic retries without an explicit policy
- workflow DSL
- persistence tied directly to Eloquent or Doctrine
