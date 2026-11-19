export const meta = {
  name: 'review-branch',
  description:
    'Review the current branch against REVIEW.md with parallel bug, security and boundary reviewers, adversarially verify each finding, and return one ranked summary',
  phases: ['Scope', 'Review', 'Verify', 'Summarize'],
}

const base = (args && args.base) || 'main'

const findingSchema = {
  type: 'object',
  required: ['findings'],
  properties: {
    findings: {
      type: 'array',
      items: {
        type: 'object',
        required: ['file', 'line', 'pass', 'severity', 'title', 'detail', 'fix'],
        properties: {
          file: { type: 'string' },
          line: { type: 'integer' },
          pass: { type: 'string', enum: ['Bugs', 'Security', 'Compliance'] },
          severity: { type: 'string', enum: ['Important', 'Minor', 'Nit'] },
          title: { type: 'string' },
          detail: { type: 'string' },
          fix: { type: 'string' },
        },
      },
    },
  },
}

phase('Scope')

const scope = await agent(
  `In this repository, run \`git diff --name-only ${base}...HEAD\` and \`git status --short\`.
Return every changed file except generated or managed files: resources/js/actions/**, resources/js/routes/**,
resources/js/wayfinder/**, composer.lock, package-lock.json, .agents/**, .pi/**, AGENTS.md.
Also return the docs/changes/<folder> path if the branch adds or edits one, otherwise an empty string.`,
  {
    label: 'scope',
    model: 'haiku',
    schema: {
      type: 'object',
      required: ['files', 'changeFolder'],
      properties: {
        files: { type: 'array', items: { type: 'string' } },
        changeFolder: { type: 'string' },
      },
    },
  },
)

if (scope.files.length === 0) {
  return `No reviewable changes between ${base} and HEAD.`
}

const fileList = scope.files.join('\n')
const specNote = scope.changeFolder
  ? `The change is described in ${scope.changeFolder}/spec.md and plan.md; flag drift from them under the Compliance pass.`
  : 'No docs/changes folder is linked to this branch.'

const sharedBrief = `Read REVIEW.md for severity meaning and what not to report.
Scope: only these files, diffed with \`git diff ${base}...HEAD -- <file>\`:
${fileList}
${specNote}
Return findings only; do not edit anything. An empty list is a valid answer.`

phase('Review')

const reviews = await parallel([
  () =>
    agent(
      `Bugs pass. Look for logic errors, broken edge cases, regressions, N+1 queries, missing transactions, and unhandled nulls. Tag every finding pass=Bugs.\n${sharedBrief}`,
      { label: 'bugs', model: 'sonnet', schema: findingSchema },
    ),
  () =>
    agent(`Security pass. Tag every finding pass=Security.\n${sharedBrief}`, {
      label: 'security',
      agentType: 'security-reviewer',
      schema: findingSchema,
    }),
  () =>
    agent(
      `Compliance pass for module boundaries, rules, and spec/plan drift. Also flag behavior changes that ship without tests. Tag every finding pass=Compliance.\n${sharedBrief}`,
      { label: 'boundaries', agentType: 'boundary-reviewer', schema: findingSchema },
    ),
])

const allFindings = reviews.filter(Boolean).flatMap((review) => review.findings)
const nits = allFindings.filter((finding) => finding.severity === 'Nit')
const serious = allFindings.filter((finding) => finding.severity !== 'Nit')

phase('Verify')

const verdicts = await pipeline(serious, (finding) =>
  agent(
    `You are an adversarial verifier. Try to DISPROVE this review finding by reading the actual code and its callers, tests, middleware, and policies.
Finding (${finding.pass}/${finding.severity}) at ${finding.file}:${finding.line}: ${finding.title}
${finding.detail}
Confirm it only if you can point to the code that makes it real. If it is real but over- or under-rated, give the corrected severity per REVIEW.md.`,
    {
      label: `verify ${finding.file}:${finding.line}`,
      model: 'sonnet',
      schema: {
        type: 'object',
        required: ['confirmed', 'severity', 'reason'],
        properties: {
          confirmed: { type: 'boolean' },
          severity: { type: 'string', enum: ['Important', 'Minor', 'Nit'] },
          reason: { type: 'string' },
        },
      },
    },
  ),
)

const confirmed = []
const unverified = []

serious.forEach((finding, index) => {
  const verdict = verdicts[index]

  if (!verdict) {
    unverified.push(finding)
  } else if (verdict.confirmed) {
    confirmed.push({ ...finding, severity: verdict.severity, evidence: verdict.reason })
  }
})

log(
  `${allFindings.length} raw findings: ${confirmed.length} confirmed, ${unverified.length} unverified, ${nits.length} nits`,
)

phase('Summarize')

return await agent(
  `Write the final review of the branch (${base}...HEAD) following REVIEW.md.
Deduplicate findings that describe the same issue. Rank them Important, then Minor, then Nit, and tag each with its pass.
Report at most five nits, then summarize the rest in one line. List unverified findings separately, marked as unverified.
Use markdown with a one-line verdict at the top: "Blocking issues found" if any Important finding remains, otherwise "No blocking issues".
Do not edit files.

Confirmed findings (JSON): ${JSON.stringify(confirmed)}
Unverified findings (JSON): ${JSON.stringify(unverified)}
Nits (JSON): ${JSON.stringify(nits)}`,
  { label: 'summary', model: 'sonnet' },
)
