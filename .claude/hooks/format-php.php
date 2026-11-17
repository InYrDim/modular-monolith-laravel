<?php

/**
 * PostToolUse hook for Edit|Write|MultiEdit.
 *
 * Runs Pint on the PHP file Claude just changed so formatting never
 * reaches review. Failures are reported but never block the session.
 */
$payload = json_decode(stream_get_contents(STDIN), true) ?: [];
$filePath = $payload['tool_input']['file_path'] ?? null;

if (! is_string($filePath) || ! str_ends_with($filePath, '.php') || ! is_file($filePath)) {
    exit(0);
}

$projectDir = getenv('CLAUDE_PROJECT_DIR') ?: getcwd();
$pint = $projectDir.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'pint';

if (! is_file($pint)) {
    exit(0);
}

exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($pint).' --quiet '.escapeshellarg($filePath).' 2>&1', $output, $exitCode);

if ($exitCode !== 0) {
    fwrite(STDERR, "Pint could not format {$filePath}:\n".implode("\n", $output)."\n");
}

exit(0);
