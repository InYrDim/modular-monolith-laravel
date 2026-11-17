<?php

/**
 * PreToolUse hook for Edit|Write|MultiEdit|NotebookEdit.
 *
 * Blocks edits to paths that must never be hand-edited by an agent:
 * secrets, dependencies, generated Wayfinder output, lock files, and
 * migrations already committed to git. Exit code 2 blocks the tool call
 * and feeds STDERR back to Claude.
 */
$payload = json_decode(stream_get_contents(STDIN), true) ?: [];
$filePath = $payload['tool_input']['file_path'] ?? $payload['tool_input']['notebook_path'] ?? null;

if (! is_string($filePath) || $filePath === '') {
    exit(0);
}

$projectDir = rtrim(str_replace('\\', '/', getenv('CLAUDE_PROJECT_DIR') ?: getcwd()), '/');
$normalizedPath = str_replace('\\', '/', $filePath);
$relativePath = str_starts_with(strtolower($normalizedPath), strtolower($projectDir.'/'))
    ? substr($normalizedPath, strlen($projectDir) + 1)
    : preg_replace('#^\./#', '', $normalizedPath);

/** @var array<string, string> $protectedPatterns */
$protectedPatterns = [
    '#^\.env(?!\.example$)(\..+)?$#' => 'environment files hold secrets. Ask the user to change .env, or edit .env.example for new keys.',
    '#^(vendor|node_modules)/#' => 'dependencies are managed by composer/npm; never edit them in place.',
    '#^resources/js/(actions|routes|wayfinder)/#' => 'Wayfinder output is generated. Change the PHP route or controller and run `php artisan wayfinder:generate`.',
    '#^(composer\.lock|package-lock\.json)$#' => 'lock files are written by composer/npm, not by hand.',
];

foreach ($protectedPatterns as $pattern => $reason) {
    if (preg_match($pattern, $relativePath) === 1) {
        fwrite(STDERR, "Blocked edit to {$relativePath}: {$reason}\n");
        exit(2);
    }
}

if (preg_match('#^(database|modules/[^/]+/database)/migrations/.+\.php$#', $relativePath) === 1) {
    exec('git -C '.escapeshellarg($projectDir).' ls-files --error-unmatch '.escapeshellarg($relativePath).' 2>&1', $output, $exitCode);

    if ($exitCode === 0) {
        fwrite(STDERR, "Blocked edit to {$relativePath}: committed migrations are immutable. Create a new migration in the owning module instead.\n");
        exit(2);
    }
}

exit(0);
