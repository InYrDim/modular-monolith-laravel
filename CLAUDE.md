# Claude Code instructions

@AGENTS.md

Project rules live in `.ai/rules/`. Read `.ai/rules/index.md` first, then every rule file whose globs match the paths you are about to change.

Starting a product from this template? Follow `docs/getting-started.md` once.

## Workflow (AI-native SDLC)

Substantial work goes intent → spec → plan → build → verify → review, with artifacts in `docs/changes/<YYYY-MM-DD>-<slug>/`. Use the `ai-sdlc` skill, and see `docs/sdlc.md`. Small changes skip the artifacts but still start with a written plan in chat. Never implement before the plan is approved.

## Orchestration

Default to one session. Delegate only independent work, and use `docs/agent-orchestration.md` to pick a subagent or team. Subagents: `test-writer`, `boundary-reviewer`, `security-reviewer`, `verifier`. Plan mode and dynamic workflows are disabled in `.claude/settings.json`. Every delegation states its objective, output format, tools/sources, and boundaries (files owned). Parallel workers never edit the same files.

## Commands

- Dev: `composer dev` (or the Herd site, never `php artisan serve`)
- Format: `vendor/bin/pint --dirty --format agent` (a hook also formats each edited PHP file)
- Test: `php artisan test --compact <path|--filter=name>`
- Full gate: `composer test` (Pint check, PHPStan level 7, Pest), then `npm run check`, `npm run types:check`, `npm run build`
- New module or contract: `php artisan make:module <Name>`, `php artisan make:contract <Module> <Name>`

## Verifying your work

Before reporting any task complete, run the full gate (or delegate to the `verifier` subagent) and paste the real result. All commands must exit 0. Never skip, weaken, or delete a failing test to get green.

## Guardrails (enforced by `.claude/settings.json` hooks)

Edits to `.env*` (except `.env.example`), `vendor/`, `node_modules/`, generated Wayfinder output, lock files, and committed migrations are blocked. Pushes to `main`, `--no-verify`, and production actions without `RELEASE_APPROVAL` are blocked. When blocked, change approach; do not work around the hook.

## Things Claude gets wrong

Add a line here when the same mistake happens twice.

- Reaching into another module's `Models`/`Actions` instead of adding a consumer-driven contract.
- Scaffolding `src/Contracts`, provider wiring, or API routes "for later". Add them only when something uses them.
- Forgetting to register module listeners/commands in the module provider (they are not auto-discovered).
- Forgetting `php artisan wayfinder:generate` after changing routes or controllers.
