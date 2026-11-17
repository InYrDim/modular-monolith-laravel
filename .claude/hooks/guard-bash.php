<?php

/**
 * PreToolUse hook for Bash.
 *
 * Approval gates the agent cannot pass on its own:
 * - production deploys and production-environment Artisan commands need
 *   RELEASE_APPROVAL set by a human in the session environment;
 * - direct pushes to main/master are refused (work goes through a PR);
 * - skipping git hooks with --no-verify is refused.
 */
$payload = json_decode(stream_get_contents(STDIN), true) ?: [];
$command = $payload['tool_input']['command'] ?? '';

if (! is_string($command) || $command === '') {
    exit(0);
}

$touchesProduction = preg_match('/--env[= ]production|\bdeploy\b.*\bprod(uction)?\b|\bprod(uction)?\b.*\bdeploy\b/i', $command) === 1;

if ($touchesProduction && (getenv('RELEASE_APPROVAL') ?: '') === '') {
    fwrite(STDERR, "Blocked: production actions need a release authorization. Ask the user to approve and set RELEASE_APPROVAL.\n");
    exit(2);
}

if (preg_match('/\bgit\s+push\b.*\b(main|master)\b/', $command) === 1) {
    fwrite(STDERR, "Blocked: do not push to main/master directly. Push a feature branch and open a pull request.\n");
    exit(2);
}

if (preg_match('/\bgit\s+(commit|push)\b.*--no-verify\b/', $command) === 1) {
    fwrite(STDERR, "Blocked: do not skip git hooks. Fix the failing check instead.\n");
    exit(2);
}

exit(0);
