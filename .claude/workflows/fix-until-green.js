export const meta = {
  name: 'fix-until-green',
  description:
    'Run the project quality gates, fix what fails, and repeat until everything passes or two rounds in a row make no progress',
  phases: ['Check', 'Fix'],
}

const maxRounds = (args && args.maxRounds) || 5

const gateSchema = {
  type: 'object',
  required: ['passed', 'failures'],
  properties: {
    passed: { type: 'boolean' },
    failures: {
      type: 'array',
      items: {
        type: 'object',
        required: ['command', 'summary', 'files'],
        properties: {
          command: { type: 'string' },
          summary: { type: 'string' },
          files: { type: 'array', items: { type: 'string' } },
        },
      },
    },
  },
}

const runGates = (round) =>
  agent(
    `Run these from the project root, in order, and keep going after a failure:
composer test, npm run check, npm run types:check, npm run build.
Set passed=true only if every command exited 0. Report one entry per distinct failure: the command, the error in one or two lines, and the files involved.
Do not edit anything.`,
    { label: `check round ${round}`, agentType: 'verifier', schema: gateSchema },
  )

let roundsWithoutProgress = 0
let previousFailureCount = Infinity

for (let round = 1; round <= maxRounds; round++) {
  phase('Check')
  const gate = await runGates(round)

  if (gate.passed) {
    return `All quality gates pass after ${round - 1} fix round(s).`
  }

  if (gate.failures.length >= previousFailureCount) {
    roundsWithoutProgress++
  } else {
    roundsWithoutProgress = 0
  }

  if (roundsWithoutProgress >= 2) {
    return `Stopped: two rounds without progress. Remaining failures:\n${JSON.stringify(gate.failures, null, 2)}`
  }

  previousFailureCount = gate.failures.length

  phase('Fix')
  await agent(
    `Fix these quality-gate failures with the smallest correct change:
${JSON.stringify(gate.failures, null, 2)}

Rules:
- Follow CLAUDE.md and .ai/rules/modular-monolith.md.
- Fix production code, not the tests. Never skip, weaken, or delete a test, and never add @phpstan-ignore or eslint-disable to silence an error.
- Never edit committed migrations or generated files. Run \`php artisan wayfinder:generate\` if route types are stale.
- If a failure needs a product decision, leave it and say so.
Reply with what you changed and why.`,
    { label: `fix round ${round}`, model: 'sonnet' },
  )
}

const finalGate = await runGates(maxRounds + 1)

return finalGate.passed
  ? `All quality gates pass after ${maxRounds} fix rounds.`
  : `Stopped after ${maxRounds} rounds. Remaining failures:\n${JSON.stringify(finalGate.failures, null, 2)}`
