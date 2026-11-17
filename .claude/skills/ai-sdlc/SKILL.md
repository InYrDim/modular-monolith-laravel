---
name: ai-sdlc
description: 'ACTIVATE when the user starts non-trivial product work: a new feature, a new module, a behavior change touching several files, a bug whose fix is not obvious, or anything that should go through intent → spec → plan → build → verify → review. Use when the user says "new feature", "intent", "spec", "plan", "ai-sdlc", "kickoff", or references docs/sdlc.md or docs/changes/. Do NOT use for one-line fixes, copy or style tweaks, dependency bumps, or answering questions.'
license: MIT
---

# AI-native SDLC (intent → spec → plan → build → verify → review)

Based on Anthropic's AI-native SDLC playbook. Every stage ends by committing an artifact the next stage reads. Artifacts live together in `docs/changes/<YYYY-MM-DD>-<slug>/`. The full guide is `docs/sdlc.md`.

## 0. Size the work

- **Small** (one obvious change, ≤ ~3 files, no schema/auth/boundary impact): skip to step 4 and use plan mode in-session; no artifacts.
- **Substantial** (new module, migration, cross-module contract, auth/security, multi-step UI flow): run every step. Stop at each human gate.

## 1. Intent — `intent.md` (gate: product owner)

Interview the originator. Do not propose solutions yet. Fill `templates/intent.md` in the originator's own terms: the problem, the outcome, who is affected, constraints, and open questions. Write it to `docs/changes/<date>-<slug>/intent.md` with `Status: draft`, then stop and ask the user to approve it (they flip it to `accepted`).

## 2. Spec — `spec.md` (gate: product owner + tech lead for risky work)

Read the accepted `intent.md`, `.ai/rules/modular-monolith.md`, and `docs/architecture/modules.md`. Apply the relevant skills as hard constraints: `security-audit` for any auth/input/data exposure, `fortify-development` for auth, `laravel-best-practices`, `inertia-react-development`. Fill `templates/spec.md`. The **Concerns** section is mandatory. Flag every boundary, security, data, or UX question you could not settle, and never resolve one silently. Stop for approval.

## 3. Plan — `plan.md` (gate: engineer)

Enter plan mode and read the codebase without editing. Decide the home (host vs. module, rule "Adding a feature" step 1). Ask the engineer about anything the code does not answer. Fill `templates/plan.md` with exact files, order of work, risks, and **Proof**: the tests that will be written and the commands that must pass. Answer "what could break?" and "what is the riskiest step?" in Risks. Stop for approval, then commit intent/spec/plan before writing code.

## 4. Build

For multi-agent work, read `docs/agent-orchestration.md` first. Use `test-writer` to turn the plan's Proof into failing tests. Split implementation across parallel agents only when each one owns disjoint files.

Implement the plan in order. Follow `.ai/rules/` and the domain skills. Where practical, write the failing Pest test first, then make it pass, and never weaken a test to make it pass. If the plan turns out wrong, update `plan.md` first and tell the user. Do not drift silently.

## 5. Verify (before saying "done")

Delegate to the `verifier` subagent (or `/fix-until-green` when several gates are red), or run the checks yourself:

```bash
vendor/bin/pint --dirty --format agent
composer test                 # pint --test, phpstan, pest
npm run check && npm run types:check && npm run build
```

Paste the real result. If a check fails, fix it and rerun. Never report done on red, and never skip or delete a failing test.

## 6. Review and ship

Run `/review-branch` locally and fix any Important findings. Then open a PR from a feature branch (pushes to `main` are blocked by a hook). The PR body links the `docs/changes/<…>/` folder. The Claude review workflow checks the PR against `REVIEW.md`, `spec.md`, and `plan.md`. A human code owner approves the merge.

## 7. Close the loop

- A mistake Claude made twice becomes a line under "Things Claude gets wrong" in `CLAUDE.md`.
- A policy that must hold without exception becomes a hook in `.claude/hooks/`, not just prose.
- A production bug or recurring review finding becomes a new `intent.md`. That is how the loop restarts.
