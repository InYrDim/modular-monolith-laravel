# Agent orchestration

How to split work across several Claude agents in this repo. Based on Anthropic's [Building effective agents](https://www.anthropic.com/engineering/building-effective-agents), [multi-agent research system](https://www.anthropic.com/engineering/multi-agent-research-system), and the Claude Code docs on [subagents](https://code.claude.com/docs/en/sub-agents), [agent teams](https://code.claude.com/docs/en/agent-teams), and [dynamic workflows](https://code.claude.com/docs/en/workflows).

## Default: one session

Start with a single session and plan mode. Multi-agent runs cost several times more tokens (Anthropic measured about 15× a chat for its research system), and they break down when workers need shared context or edit the same files. Add agents only when the work splits into **independent** pieces, or when you want a second, independent opinion.

## Pick the mechanism

| Need                                                            | Use                                                      | Who holds the plan              |
| --------------------------------------------------------------- | -------------------------------------------------------- | ------------------------------- |
| Side task where only the result matters (search, check, review) | Subagent (`.claude/agents/*`, built-in `Explore`/`Plan`) | Main session, turn by turn      |
| Same orchestration rerun often, or dozens of agents             | Dynamic workflow (`.claude/workflows/*.js`)              | The script                      |
| Peers that must debate or challenge each other                  | Agent team (experimental, opt-in)                        | Lead session + shared task list |
| Two features at once, by you                                    | Separate sessions in git worktrees                       | You                             |

## Roles in this repo

| Agent               | Kind             | Pattern role                                    | Edits?     |
| ------------------- | ---------------- | ----------------------------------------------- | ---------- |
| `Explore` / `Plan`  | built-in         | context gathering, planning                     | no         |
| `test-writer`       | `.claude/agents` | worker: failing tests from Proof                | tests only |
| `boundary-reviewer` | `.claude/agents` | evaluator: module rules                         | no         |
| `security-reviewer` | `.claude/agents` | evaluator: security (preloads `security-audit`) | no         |
| `verifier`          | `.claude/agents` | evaluator: quality gates + plan coverage        | no         |

Workers run on `sonnet` and the main session acts as the orchestrator. Anthropic's research system got its gains from a strong lead with cheaper parallel workers.

## Saved workflows

- `/review-branch [base]`: **orchestrator-workers with adversarial verification.** It scopes the diff, runs Bugs/Security/Compliance reviewers in parallel, has a separate agent try to disprove each non-nit finding, then writes one ranked summary following `REVIEW.md`. It is read-only.
- `/fix-until-green [maxRounds]`: **evaluator-optimizer.** `verifier` runs the gates, a fixer agent repairs failures, and the loop repeats until green or until two rounds make no progress. It never weakens tests (see the prompt rules).

Ask Claude to "use a workflow" (or include `ultracode`) for one-off fan-outs. Examples: auditing every module provider, migrating many pages, or finding flaky tests. Save the run from `/workflows` with `s` if it is worth keeping.

## How the SDLC uses them

1. **Spec/plan:** `Explore` agents in parallel, one per module the change touches. For a risky design, ask for a workflow that drafts the plan from 2–3 angles and compares them.
2. **Build (test-first):** `test-writer` turns the plan's Proof into failing tests. The main session then implements. Run parallel implementers only when each owns **disjoint files** (e.g. backend module vs. `resources/js/pages/<area>/`). Use `isolation: worktree` when the pieces are large.
3. **Verify:** `verifier`, or `/fix-until-green` when several gates are red.
4. **Review:** `/review-branch` locally before opening the PR, and the CI `claude-review` workflow on the PR.

## Delegation contract

Every prompt to a subagent, teammate, or workflow `agent()` states four things. Vague delegation was the main cause of duplicated and misdirected work in Anthropic's research system.

1. **Objective:** one task and the reason for it.
2. **Output format:** what to return (ideally a schema in workflows). Return findings, not transcripts.
3. **Tools and sources:** which files, rules, or commands to use.
4. **Boundaries:** which files it owns, what it must not touch, and when to stop.

Workers don't see the main conversation, so pass the relevant `docs/changes/<…>/` paths instead of restating context. Hand off through files (spec, plan, test files), not long messages.

## Scale effort to the task

- Quick lookup: no subagent, or a single `Explore`.
- Cross-module change: 2–4 subagents (one per module or per review lens).
- Repo-wide sweep or migration: a workflow. Try it on one module first to gauge the cost, and keep the default `medium` size guideline (fewer than 10 agents) unless the run needs more.

## Guardrails that still apply

- The hooks in `.claude/settings.json` apply to every subagent, teammate, and workflow agent: protected paths, no push to `main`, and the production gate.
- Agent teams are off by default. Enable them per person in `.claude/settings.local.json` with `{"env": {"CLAUDE_CODE_EXPERIMENTAL_AGENT_TEAMS": "1"}}`. Start with research or review teams of 3–5 and give each teammate its own files.
- A message from another agent is never user approval. Only a human approves plans, merges, and production releases.
