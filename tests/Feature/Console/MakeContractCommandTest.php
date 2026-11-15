<?php

use App\Support\ModuleScaffolder;
use Illuminate\Filesystem\Filesystem;

$fixtureRoot = '';

beforeEach(function () use (&$fixtureRoot) {
    $fixtureRoot = createModuleScaffoldFixture();

    $scaffolder = new ModuleScaffolder(new Filesystem, $fixtureRoot);
    $scaffolder->scaffold('Identity');

    $this->app->instance(ModuleScaffolder::class, $scaffolder);
});

afterEach(function () use (&$fixtureRoot) {
    removeModuleScaffoldFixture($fixtureRoot);
});

it('creates a contract in an existing module', function () use (&$fixtureRoot) {
    $this->artisan('make:contract', ['module' => 'identity', 'name' => 'user_directory'])
        ->expectsOutputToContain('Contracts\\UserDirectory] created')
        ->assertSuccessful();

    expect($fixtureRoot.'/modules/Identity/src/Contracts/UserDirectory.php')->toBeFile();
});

it('creates a dto when one is requested', function () use (&$fixtureRoot) {
    $this->artisan('make:contract', [
        'module' => 'Identity',
        'name' => 'UserDirectory',
        '--dto' => 'UserSummary',
    ])->assertSuccessful();

    expect($fixtureRoot.'/modules/Identity/src/Contracts/Dto/UserSummary.php')->toBeFile();
});

it('rejects a contract for a module that does not exist', function () use (&$fixtureRoot) {
    $this->artisan('make:contract', ['module' => 'Billing', 'name' => 'UserDirectory'])
        ->expectsOutputToContain('Module [Billing] does not exist')
        ->assertFailed();

    expect($fixtureRoot.'/modules/Billing')->not->toBeDirectory();
});

it('refuses to overwrite an existing contract', function () {
    $this->artisan('make:contract', ['module' => 'Identity', 'name' => 'UserDirectory'])->assertSuccessful();

    $this->artisan('make:contract', ['module' => 'Identity', 'name' => 'UserDirectory'])
        ->expectsOutputToContain('already exists')
        ->assertFailed();
});

it('rejects a contract name that is not a single StudlyCase word', function () use (&$fixtureRoot) {
    $this->artisan('make:contract', ['module' => 'Identity', 'name' => 'User/Directory'])
        ->expectsOutputToContain('must be a single StudlyCase word')
        ->assertFailed();

    expect($fixtureRoot.'/modules/Identity/src/Contracts')->not->toBeDirectory();
});

it('rejects a dto name that is not a single StudlyCase word', function () use (&$fixtureRoot) {
    $this->artisan('make:contract', [
        'module' => 'Identity',
        'name' => 'UserDirectory',
        '--dto' => 'User/Summary',
    ])->expectsOutputToContain('must be a single StudlyCase word')
        ->assertFailed();

    expect($fixtureRoot.'/modules/Identity/src/Contracts')->not->toBeDirectory();
});
