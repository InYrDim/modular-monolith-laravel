# AI-native SDLC

This project follows Anthropic's [AI-native SDLC playbook](https://claude.com/resources/articles/the-ai-native-sdlc-playbook). Delivery is a loop, not a line. Each stage ends by committing an artifact the next stage reads, and a human approves at every gate.

```
intent.md ─► spec.md ─► plan.md ─► code + tests ─► verify ─► PR review ─► merge/deploy
    ▲                                                                         │
    └──────────── production bugs, recurring review findings ◄────────────────┘
```

## Stages, artifacts, gates

| Stage    | Artifact / mechanism                                        | Who approves                   |
| -------- | ----------------------------------------------------------- | ------------------------------ |
| Intent   | `docs/changes/<date>-<slug>/intent.md`                      | Product owner                  |
| Spec     | `spec.md` with a mandatory **Concerns** table               | Product owner (+ tech lead)    |
| Plan     | `plan.md` written in Claude Code plan mode                  | Engineer                       |
| Build    | Code following `.ai/rules/` and skills; hooks as guardrails | —                              |
| Verify   | `verifier` subagent / `composer test` + frontend checks     | Must be green before "done"    |
| Review   | `claude-review` workflow using `REVIEW.md`                  | Code owner (branch protection) |
| Maintain | Incidents and repeat findings become a new `intent.md`      | Service owner triage           |

Templates live in `.claude/skills/ai-sdlc/templates/`. Run `/ai-sdlc` in Claude Code (or describe a new feature) to start the flow.

Small changes (one obvious edit, no schema, auth, or boundary impact) skip the artifacts but still start in plan mode.

## Where knowledge lives

- `CLAUDE.md`: commands, verification, and "Things Claude gets wrong". Keep it short. A mistake made twice gets a line here.
- `.ai/rules/`: architecture rules scoped by path.
- `.claude/skills/`: policies that must be applied consistently (security, Fortify, testing, and so on).
- `.claude/hooks/` + `.claude/settings.json`: policies that must hold without exception (protected paths, no push to `main`, production gate, auto-format).
- `.claude/agents/verifier.md`: the subagent that runs the quality gates and checks plan coverage.
- `REVIEW.md`: what AI review checks, how findings are ranked, and what to ignore.
- `.claude/agents/` + `.claude/workflows/`: worker roles and saved multi-agent workflows. See `docs/agent-orchestration.md`.

## One-time repository setup

1. Add the `ANTHROPIC_API_KEY` repository secret to enable `.github/workflows/claude-review.yml`. The job skips without it.
2. Protect `main`: require a PR, the `tests` check, and one human approval.
3. Production releases: the Bash hook blocks production deploy or `--env=production` commands unless a human starts the session with `RELEASE_APPROVAL` set.

## Not adopted yet

The playbook also describes continuous agent evals in CI (20–50 real tasks rerun when `CLAUDE.md`, skills, or hooks change), metric control bands that open an `intent.md` automatically, scheduled security scans, and Claude in the on-call channel. Add these once the product is in production and has real tasks and metrics to measure.
