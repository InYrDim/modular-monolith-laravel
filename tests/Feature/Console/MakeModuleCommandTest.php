<?php

use App\Support\ModuleScaffolder;
use Illuminate\Filesystem\Filesystem;

it('creates a module and registers it with the host', function () {
    $root = createModuleScaffoldFixture();

    try {
        $this->app->instance(ModuleScaffolder::class, new ModuleScaffolder(new Filesystem, $root));

        $this->artisan('make:module', ['name' => 'billing', '--no-autoload' => true])
            ->expectsOutputToContain('Module [Billing] created')
            ->assertSuccessful();

        expect($root.'/modules/Billing/src/Providers/BillingServiceProvider.php')->toBeFile()
            ->and($root.'/modules/Billing/routes/web.php')->toBeFile();
    } finally {
        removeModuleScaffoldFixture($root);
    }
});

it('rejects a name that is not a single StudlyCase word', function () {
    $root = createModuleScaffoldFixture();

    try {
        $this->app->instance(ModuleScaffolder::class, new ModuleScaffolder(new Filesystem, $root));

        $this->artisan('make:module', ['name' => 'Billing/Reports', '--no-autoload' => true])
            ->expectsOutputToContain('must be a single StudlyCase word')
            ->assertFailed();

        expect(glob($root.'/modules/*') ?: [])->toBeEmpty();
    } finally {
        removeModuleScaffoldFixture($root);
    }
});

it('refuses to overwrite an existing module', function () {
    $root = createModuleScaffoldFixture();

    try {
        (new Filesystem)->ensureDirectoryExists($root.'/modules/Identity');
        $this->app->instance(ModuleScaffolder::class, new ModuleScaffolder(new Filesystem, $root));

        $this->artisan('make:module', ['name' => 'Identity', '--no-autoload' => true])
            ->expectsOutputToContain('Module [Identity] already exists')
            ->assertFailed();

        expect($root.'/modules/Identity/src/Providers/IdentityServiceProvider.php')->not->toBeFile();
    } finally {
        removeModuleScaffoldFixture($root);
    }
});

it('is registered with the console kernel', function () {
    $this->artisan('list')
        ->expectsOutputToContain('make:module')
        ->assertSuccessful();
});
