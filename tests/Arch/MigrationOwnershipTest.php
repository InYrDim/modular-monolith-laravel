<?php

/**
 * The host owns infrastructure tables only. Every domain table belongs to the
 * module that owns the bounded context, so its migration must live there.
 */
it('creates only infrastructure tables in the host migrations', function () {
    $allowed = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'password_reset_tokens',
        'sessions',
    ];

    $tables = [];

    foreach (glob(dirname(__DIR__, 2).'/database/migrations/*.php') ?: [] as $migration) {
        preg_match_all(
            '/Schema::(?:create|createIfNotExists|table|drop|dropIfExists|rename)\(\s*[\'"]([^\'"]+)[\'"]/',
            (string) file_get_contents($migration),
            $matches,
        );

        $tables = [...$tables, ...$matches[1]];
    }

    expect($tables)->not->toBeEmpty();
    expect(array_values(array_diff(array_unique($tables), $allowed)))->toBe([]);
});
