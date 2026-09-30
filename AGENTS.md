# Development guidance

## Purpose

This repository provides a framework-agnostic PHP process tracking library with Laravel and Symfony integrations.

## Architecture rules

- Keep the core independent of Laravel, Symfony, Doctrine, HTTP clients and queues.
- Put framework-specific code under explicit integration namespaces.
- Prefer immutable value objects for identifiers and state data.
- Keep persistence behind interfaces.
- Keep process state transitions explicit and testable.
- Do not hide business behavior behind magic.
- Public APIs must be documented with examples.
- Backward compatibility matters once the first stable release exists.

## Development workflow

- `main`: stable, releasable code.
- `dev`: integration branch.
- `feature/*`: short-lived branch for one coherent task.
- No pull requests are required for this project workflow; integrate feature branches into `dev` directly.
- Create release tags from `main`.

## Quality gate

Every feature must have tests, pass static analysis and preserve the documented public API.
