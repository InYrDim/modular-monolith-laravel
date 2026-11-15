<?php

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * Module pages live in resources/js/pages/<module>/. They reach other modules
 * through HTTP routes and PHP contracts, never by importing another module's
 * Wayfinder controller actions.
 */
it('keeps module pages from importing another module controller actions', function () {
    $root = dirname(__DIR__, 2);
    $modules = array_map('basename', glob($root.'/modules/*', GLOB_ONLYDIR) ?: []);
    $violations = [];

    foreach ($modules as $module) {
        $pagesDirectory = $root.'/resources/js/pages/'.Str::lower($module);

        if (! is_dir($pagesDirectory)) {
            continue;
        }

        foreach (Finder::create()->files()->in($pagesDirectory)->name(['*.ts', '*.tsx']) as $file) {
            preg_match_all(
                '~[\'"](@/actions/Modules/([^/\'"]+)/)~',
                $file->getContents(),
                $matches,
                PREG_SET_ORDER,
            );

            foreach ($matches as $match) {
                if ($match[2] === $module) {
                    continue;
                }

                $violations[] = Str::lower($module).' pages: '.$file->getRelativePathname().' imports '.$match[1];
            }
        }
    }

    expect($violations)->toBe([]);
});
