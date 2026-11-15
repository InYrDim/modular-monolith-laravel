<?php

$moduleDirectory = dirname(__DIR__, 2).'/modules';
$modules = array_map('basename', glob($moduleDirectory.'/*', GLOB_ONLYDIR) ?: []);

foreach ($modules as $module) {
    arch("{$module} does not depend on the host app")
        ->expect("Modules\\{$module}")
        ->not->toUse('App');

    arch("host app uses {$module} only through its Contracts")
        ->expect('App')
        ->not->toUse("Modules\\{$module}")
        ->ignoring("Modules\\{$module}\\Contracts");

    foreach (array_diff($modules, [$module]) as $other) {
        arch("{$module} uses {$other} only through its Contracts")
            ->expect("Modules\\{$module}")
            ->not->toUse("Modules\\{$other}")
            ->ignoring("Modules\\{$other}\\Contracts");
    }
}

// Identity must not depend on any business module at all.
foreach (array_diff($modules, ['Identity']) as $business) {
    arch("Identity does not depend on {$business}")
        ->expect('Modules\\Identity')
        ->not->toUse("Modules\\{$business}");
}
