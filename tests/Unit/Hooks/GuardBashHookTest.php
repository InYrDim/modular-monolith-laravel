<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Run the Claude Code Bash guard hook against a command and return its exit code.
 */
function runGuardBashHook(string $command, string $cwd): int
{
    $process = new Process([PHP_BINARY, dirname(__DIR__, 3).'/.claude/hooks/guard-bash.php']);
    $process->setInput((string) json_encode(['cwd' => $cwd, 'tool_input' => ['command' => $command]]));
    $process->run();

    return (int) $process->getExitCode();
}

/**
 * Create an empty git repository with the given branch checked out.
 *
 * Pass `$removeAll: true` to delete every repository created so far.
 */
function gitRepositoryOn(string $branch, bool $removeAll = false): string
{
    /** @var list<string> $created */
    static $created = [];

    if ($removeAll) {
        array_map(fn (string $directory): bool => (new Filesystem)->deleteDirectory($directory), $created);
        $created = [];

        return '';
    }

    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'guard-bash-'.Str::random(16);
    (new Filesystem)->ensureDirectoryExists($directory);
    (new Process(['git', 'init', '-q', '-b', $branch], $directory))->mustRun();
    $created[] = $directory;

    return $directory;
}

afterEach(function () {
    gitRepositoryOn('', removeAll: true);
});

dataset('pushes to a protected branch', [
    'named main' => 'git push origin main',
    'named master' => 'git push origin master',
    'force to main' => 'git push -f origin +main',
    'HEAD to main' => 'git push origin HEAD:main',
    'full ref' => 'git push origin feature:refs/heads/main',
    'delete main' => 'git push origin --delete main',
    'all branches' => 'git push --all origin',
    'after a chained command' => 'composer test && git push origin main',
    'inside a heredoc run by a shell' => "bash <<'EOF'\ngit push origin main\nEOF",
]);

dataset('pushes to a feature branch', [
    'branch ending in -main' => 'git push -u origin chore/template-neutral-main',
    'delete branch named from-main' => 'git push origin --delete example-from-main',
    'main only in a later command' => 'git push origin feature && git switch main',
    'main only in a remote ref' => 'git push --force-with-lease origin example/billing && git log origin/main',
    'main only in a commit message heredoc' => "git add guard-bash.php && git commit -F - <<'EOF'\nlet a bare `git push` from main through\nEOF\ngit push -u origin fix/x",
]);

it('blocks a push to main or master', function (string $command) {
    expect(runGuardBashHook($command, gitRepositoryOn('feature')))->toBe(2);
})->with('pushes to a protected branch');

it('allows a push to a feature branch', function (string $command) {
    expect(runGuardBashHook($command, gitRepositoryOn('feature')))->toBe(0);
})->with('pushes to a feature branch');

it('blocks a push without a destination while main is checked out', function (string $command) {
    expect(runGuardBashHook($command, gitRepositoryOn('main')))->toBe(2);
})->with(['git push', 'git push origin', 'git push origin HEAD']);

it('allows a push without a destination while a feature branch is checked out', function () {
    expect(runGuardBashHook('git push', gitRepositoryOn('feature')))->toBe(0);
});

it('follows a switch earlier in the same command', function () {
    expect(runGuardBashHook('git switch main && git push', gitRepositoryOn('feature')))->toBe(2)
        ->and(runGuardBashHook('git switch -q -c fix/x && git push -u origin', gitRepositoryOn('main')))->toBe(0);
});
