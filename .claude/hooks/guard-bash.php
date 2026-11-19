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

if (preg_match('/\bgit\b.*\bpush\b/s', $command) === 1 && pushesToProtectedBranch($command, currentBranch($payload['cwd'] ?? null))) {
    fwrite(STDERR, "Blocked: do not push to main/master directly. Push a feature branch and open a pull request.\n");
    exit(2);
}

if (preg_match('/\bgit\s+(commit|push)\b.*--no-verify\b/', $command) === 1) {
    fwrite(STDERR, "Blocked: do not skip git hooks. Fix the failing check instead.\n");
    exit(2);
}

exit(0);

/**
 * The branch checked out in the session's working directory, or "" outside a repository.
 */
function currentBranch(mixed $cwd): string
{
    $directory = is_string($cwd) && $cwd !== '' ? ' -C '.escapeshellarg($cwd) : '';
    $silence = PHP_OS_FAMILY === 'Windows' ? '2>NUL' : '2>/dev/null';

    return trim((string) shell_exec("git{$directory} branch --show-current {$silence}"));
}

/**
 * Whether any `git push` in the command targets main or master.
 *
 * The command is split into its chained parts, so a branch name that only
 * contains "main" (chore/neutral-main) or a later `git switch main` does not
 * count. A push without a destination, or to HEAD, targets the current branch,
 * which a preceding `git switch` / `git checkout` in the same command updates.
 */
function pushesToProtectedBranch(string $command, string $currentBranch): bool
{
    $protected = ['main', 'master'];
    $gitPrefix = '\bgit(?:\s+-[cC]\s+\S+)*\s+';

    // Heredoc bodies are data (commit messages, PR bodies), unless a shell reads them as commands.
    $command = preg_replace_callback(
        '/^(?<opening>[^\n]*<<-?\s*([\'"]?)(?<marker>\w+)\2[^\n]*)\n.*?\n\s*\k<marker>\s*$/ms',
        fn (array $heredoc): string => preg_match('/(?:^|[\s;&|(])(?:bash|sh|zsh)(?:\s+-\S+)*\s*<</', $heredoc['opening']) === 1 ? $heredoc[0] : $heredoc['opening'],
        $command,
    ) ?? $command;

    foreach (preg_split('/&&|\|\||[;|\n]/', $command) ?: [] as $part) {
        if (preg_match('/'.$gitPrefix.'(?:switch|checkout)\s+(?:-\S+\s+)*([^\s-]\S*)\s*$/', $part, $switch) === 1) {
            $currentBranch = $switch[1];

            continue;
        }

        if (preg_match('/'.$gitPrefix.'push\b(.*)$/s', $part, $push) !== 1) {
            continue;
        }

        $arguments = preg_split('/\s+/', trim($push[1]), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (array_intersect($arguments, ['--all', '--mirror']) !== []) {
            return true;
        }

        $refspecs = array_slice(array_values(array_filter(
            $arguments,
            fn (string $argument): bool => ! str_starts_with($argument, '-'),
        )), 1);

        if ($refspecs === []) {
            $refspecs = [$currentBranch];
        }

        foreach ($refspecs as $refspec) {
            $destination = ltrim($refspec, '+');
            $destination = str_contains($destination, ':') ? substr($destination, strpos($destination, ':') + 1) : $destination;
            $destination = preg_replace('#^refs/heads/#', '', $destination) ?? $destination;

            if ($destination === 'HEAD' || $destination === '') {
                $destination = $currentBranch;
            }

            if (in_array($destination, $protected, true)) {
                return true;
            }
        }
    }

    return false;
}
