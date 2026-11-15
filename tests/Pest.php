<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../modules/*/tests');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Create a throwaway application root for scaffolding tests.
 *
 * Returns the absolute path of a directory containing a minimal composer.json,
 * bootstrap/providers.php, and an empty modules directory.
 */
function createModuleScaffoldFixture(): string
{
    $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'module-scaffolder-'.Str::random(16);
    $files = new Filesystem;

    $files->ensureDirectoryExists($root.'/bootstrap');
    $files->ensureDirectoryExists($root.'/modules');

    $files->put($root.'/composer.json', json_encode([
        'name' => 'fixture/application',
        'autoload' => [
            'psr-4' => [
                'App\\' => 'app/',
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

    $files->put($root.'/bootstrap/providers.php', <<<'PHP'
<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
];
PHP);

    return $root;
}

function removeModuleScaffoldFixture(string $root): void
{
    (new Filesystem)->deleteDirectory($root);
}
