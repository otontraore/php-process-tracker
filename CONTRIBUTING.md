# Contributing

## Branch model

Keep the branch model deliberately small:

- `main`: stable and releasable.
- `dev`: active integration branch.
- `feature/<short-name>`: one task at a time, short-lived.
- Git tags such as `v0.1.0`, `v0.2.0`, `v1.0.0`: releases.

Do not create permanent branches for versions, environments or individual developers.

## Flow

1. Start from the latest `dev`.
2. Create one `feature/*` branch for the task.
3. Develop and test locally.
4. Push the feature branch.
5. Verify CI.
6. Integrate the feature into `dev` without a pull request.
7. Delete the feature branch.
8. When the release acceptance criteria are met, integrate `dev` into `main`.
9. Create the release tag from `main`.

## Commit style

Use clear conventional-style messages:

- `feat:`
- `fix:`
- `test:`
- `refactor:`
- `docs:`
- `ci:`
- `chore:`

Keep commits focused and reversible.

## Quality

A change is not complete until:
- automated tests pass
- static analysis passes
- public API documentation is updated when needed
- backward compatibility has been considered
- security and failure cases have tests where relevant
