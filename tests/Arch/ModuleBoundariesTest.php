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

    if (is_dir("{$moduleDirectory}/{$module}/src/Contracts")) {
        arch("{$module} Contracts do not leak {$module} internals")
            ->expect("Modules\\{$module}\\Contracts")
            ->not->toUse([
                "Modules\\{$module}\\Actions",
                "Modules\\{$module}\\Concerns",
                "Modules\\{$module}\\Http",
                "Modules\\{$module}\\Models",
                "Modules\\{$module}\\Providers",
            ]);
    }
}

it('registers a service provider for every module', function () {
    $root = dirname(__DIR__, 2);
    $modules = array_map('basename', glob($root.'/modules/*', GLOB_ONLYDIR) ?: []);
    $providers = file_get_contents($root.'/bootstrap/providers.php');

    expect($modules)->not->toBeEmpty()
        ->and($providers)->toBeString();

    foreach ($modules as $module) {
        expect($providers)
            ->toContain("use Modules\\{$module}\\Providers\\{$module}ServiceProvider;")
            ->toContain("{$module}ServiceProvider::class,");
    }
});

it('gives every module a service provider class file', function () {
    $root = dirname(__DIR__, 2);
    $modules = array_map('basename', glob($root.'/modules/*', GLOB_ONLYDIR) ?: []);

    expect($modules)->not->toBeEmpty();

    foreach ($modules as $module) {
        expect($root."/modules/{$module}/src/Providers/{$module}ServiceProvider.php")
            ->toBeFile();
    }
});

// Identity must not depend on any business module at all.
foreach (array_diff($modules, ['Identity']) as $business) {
    arch("Identity does not depend on {$business}")
        ->expect('Modules\\Identity')
        ->not->toUse("Modules\\{$business}");
}
