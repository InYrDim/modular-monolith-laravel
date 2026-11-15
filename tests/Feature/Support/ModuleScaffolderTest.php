<?php

use App\Support\ModuleScaffolder;
use Illuminate\Filesystem\Filesystem;

it('creates the module layout and registers it with the host', function () {
    $root = createModuleScaffoldFixture();

    try {
        $paths = (new ModuleScaffolder(new Filesystem, $root))->scaffold('Billing');

        expect($paths)->toHaveCount(4)
            ->and($root.'/modules/Billing/src/Providers/BillingServiceProvider.php')->toBeFile()
            ->and($root.'/modules/Billing/routes/web.php')->toBeFile()
            ->and($root.'/modules/Billing/routes/api.php')->not->toBeFile()
            ->and($root.'/modules/Billing/database/migrations')->toBeDirectory()
            ->and($root.'/modules/Billing/tests/Feature')->toBeDirectory();

        $composer = json_decode((string) file_get_contents($root.'/composer.json'), true);

        expect($composer['autoload']['psr-4'])->toMatchArray([
            'App\\' => 'app/',
            'Modules\\Billing\\' => 'modules/Billing/src/',
            'Modules\\Billing\\Database\\Factories\\' => 'modules/Billing/database/factories/',
            'Modules\\Billing\\Database\\Seeders\\' => 'modules/Billing/database/seeders/',
        ]);

        $provider = (string) file_get_contents($root.'/modules/Billing/src/Providers/BillingServiceProvider.php');

        expect($provider)
            ->toContain('namespace Modules\\Billing\\Providers;')
            ->toContain("loadMigrationsFrom(__DIR__.'/../../database/migrations')")
            ->toContain("Route::middleware('web')->group(__DIR__.'/../../routes/web.php')")
            ->not->toContain("middleware('api')");

        $providers = (string) file_get_contents($root.'/bootstrap/providers.php');

        expect($providers)
            ->toContain('use Modules\\Billing\\Providers\\BillingServiceProvider;')
            ->toContain('    BillingServiceProvider::class,');
    } finally {
        removeModuleScaffoldFixture($root);
    }
});

it('scaffolds api routes only when requested', function () {
    $root = createModuleScaffoldFixture();

    try {
        (new ModuleScaffolder(new Filesystem, $root))->scaffold('Billing', withApiRoutes: true);

        $provider = (string) file_get_contents($root.'/modules/Billing/src/Providers/BillingServiceProvider.php');

        expect($root.'/modules/Billing/routes/api.php')->toBeFile()
            ->and($provider)->toContain("Route::middleware('api')->prefix('api')->group(__DIR__.'/../../routes/api.php')");
    } finally {
        removeModuleScaffoldFixture($root);
    }
});

it('does not duplicate registrations when the same module is scaffolded twice', function () {
    $root = createModuleScaffoldFixture();

    try {
        $scaffolder = new ModuleScaffolder(new Filesystem, $root);
        $scaffolder->scaffold('Billing');
        $scaffolder->scaffold('Billing');

        $composer = json_decode((string) file_get_contents($root.'/composer.json'), true);
        $providers = (string) file_get_contents($root.'/bootstrap/providers.php');

        expect(array_keys($composer['autoload']['psr-4']))->toHaveCount(4)
            ->and(substr_count($providers, 'use Modules\\Billing\\Providers\\BillingServiceProvider;'))->toBe(1)
            ->and(substr_count($providers, 'BillingServiceProvider::class'))->toBe(1);
    } finally {
        removeModuleScaffoldFixture($root);
    }
});

it('reports whether a module already exists', function () {
    $root = createModuleScaffoldFixture();

    try {
        $scaffolder = new ModuleScaffolder(new Filesystem, $root);

        expect($scaffolder->exists('Billing'))->toBeFalse();

        $scaffolder->scaffold('Billing');

        expect($scaffolder->exists('Billing'))->toBeTrue();
    } finally {
        removeModuleScaffoldFixture($root);
    }
});

it('creates a public contract without an implementation or dto', function () {
    $root = createModuleScaffoldFixture();

    try {
        $scaffolder = new ModuleScaffolder(new Filesystem, $root);
        $scaffolder->scaffold('Identity');

        $paths = $scaffolder->scaffoldContract('Identity', 'UserDirectory');

        expect($paths)->toHaveCount(1)
            ->and($root.'/modules/Identity/src/Contracts/UserDirectory.php')->toBeFile()
            ->and($root.'/modules/Identity/src/Contracts/Dto')->not->toBeDirectory()
            ->and($scaffolder->contractExists('Identity', 'UserDirectory'))->toBeTrue();

        expect((string) file_get_contents($root.'/modules/Identity/src/Contracts/UserDirectory.php'))
            ->toContain('namespace Modules\Identity\Contracts;')
            ->toContain('interface UserDirectory')
            ->not->toContain('Modules\Identity\Models');
    } finally {
        removeModuleScaffoldFixture($root);
    }
});

it('creates a readonly dto next to the contract when requested', function () {
    $root = createModuleScaffoldFixture();

    try {
        $scaffolder = new ModuleScaffolder(new Filesystem, $root);
        $scaffolder->scaffold('Identity');

        $scaffolder->scaffoldContract('Identity', 'UserDirectory', 'UserSummary');

        expect((string) file_get_contents($root.'/modules/Identity/src/Contracts/Dto/UserSummary.php'))
            ->toContain('namespace Modules\Identity\Contracts\Dto;')
            ->toContain('final readonly class UserSummary')
            ->not->toContain('Modules\Identity\Models');
    } finally {
        removeModuleScaffoldFixture($root);
    }
});
