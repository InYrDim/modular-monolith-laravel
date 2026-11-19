# Review instructions

Used by Claude (the `claude-review` workflow and local `/review`) for every pull request. Humans still approve merges.

## Passes

Run three passes and tag each finding with its pass:

- **Bugs:** logic errors, broken edge cases, regressions, N+1 queries, missing transactions, unhandled nulls.
- **Security:** missing authorization (policy/gate) or Form Request validation, mass assignment, raw SQL with input, secrets or PII in logs/responses, CSRF exposure on non-`web` routes, unsafe redirects.
- **Compliance:** the diff matches the linked `docs/changes/*/spec.md` and `plan.md`, and it follows `.ai/rules/modular-monolith.md`. Modules must not import `App\`. Cross-module access goes only through `Contracts`. There are no cross-module Eloquent relations. Committed migrations are not edited. Frontend pages do not import another module's actions. Behavior changes come with tests.

## Severity

- **Important:** breaks behavior, leaks data, or violates a module boundary. Only these warrant blocking.
- **Minor:** correct but fragile, unclear, or missing a test for an edge case.
- **Nit:** style or naming.

## Cap the nits

Report at most five nits per review and summarize the rest in one line.

## Do not report

- Formatting (Pint runs in CI) or type errors PHPStan/tsc already catch.
- Generated files: `resources/js/actions/**`, `resources/js/routes/**`, `resources/js/wayfinder/**`, `composer.lock`, `package-lock.json`.
- Boost-managed guidance copies in `.agents/`, `.pi/`, and `AGENTS.md`.
