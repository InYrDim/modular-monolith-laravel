<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Creates the files for a new module and registers it in the host composition root.
 *
 * The scaffolder only touches the module directory, the PSR-4 section of
 * composer.json, and bootstrap/providers.php. Regenerating the Composer
 * autoloader is left to the caller.
 */
class ModuleScaffolder
{
    /**
     * Directories created inside every new module.
     *
     * @var list<string>
     */
    private const DIRECTORIES = [
        'src/Http/Controllers',
        'src/Models',
        'src/Providers',
        'database/factories',
        'database/migrations',
        'database/seeders',
        'routes',
        'tests/Feature',
    ];

    public function __construct(
        private readonly Filesystem $files,
        private readonly string $root,
    ) {}

    public function rootPath(): string
    {
        return $this->root;
    }

    public function modulePath(string $module): string
    {
        return $this->root.'/modules/'.$module;
    }

    public function exists(string $module): bool
    {
        return $this->files->isDirectory($this->modulePath($module));
    }

    /**
     * @return list<string> The absolute paths that were created or updated.
     */
    public function scaffold(string $module): array
    {
        $modulePath = $this->modulePath($module);

        foreach (self::DIRECTORIES as $directory) {
            $this->files->ensureDirectoryExists($modulePath.'/'.$directory);
        }

        $paths = [
            $this->write(
                $modulePath.'/src/Providers/'.$module.'ServiceProvider.php',
                $this->providerStub($module),
            ),
            $this->write($modulePath.'/routes/web.php', $this->routesStub($module)),
        ];

        $paths[] = $this->registerAutoload($module);
        $paths[] = $this->registerProvider($module);

        return array_values(array_unique($paths));
    }

    public function contractPath(string $module, string $contract): string
    {
        return $this->modulePath($module).'/src/Contracts/'.$contract.'.php';
    }

    public function contractExists(string $module, string $contract): bool
    {
        return $this->files->exists($this->contractPath($module, $contract));
    }

    /**
     * Create the public contract for a consumer that already needs it.
     *
     * @return list<string> The absolute paths that were created.
     */
    public function scaffoldContract(string $module, string $contract, ?string $dto = null): array
    {
        $paths = [
            $this->write($this->contractPath($module, $contract), $this->contractStub($module, $contract)),
        ];

        if ($dto !== null) {
            $paths[] = $this->write(
                $this->modulePath($module).'/src/Contracts/Dto/'.$dto.'.php',
                $this->dtoStub($module, $dto),
            );
        }

        return $paths;
    }

    /**
     * Add the three PSR-4 mappings the module layout expects to composer.json.
     */
    private function registerAutoload(string $module): string
    {
        $path = $this->root.'/composer.json';
        $manifest = json_decode($this->files->get($path), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($manifest)) {
            throw new RuntimeException("Unable to read the composer manifest at [{$path}].");
        }

        $autoload = is_array($manifest['autoload'] ?? null) ? $manifest['autoload'] : [];
        $psr4 = is_array($autoload['psr-4'] ?? null) ? $autoload['psr-4'] : [];

        $psr4["Modules\\{$module}\\"] = "modules/{$module}/src/";
        $psr4["Modules\\{$module}\\Database\\Factories\\"] = "modules/{$module}/database/factories/";
        $psr4["Modules\\{$module}\\Database\\Seeders\\"] = "modules/{$module}/database/seeders/";

        $autoload['psr-4'] = $psr4;
        $manifest['autoload'] = $autoload;

        $this->files->put($path, json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        )."\n");

        return $path;
    }

    /**
     * Register the module provider in bootstrap/providers.php.
     */
    private function registerProvider(string $module): string
    {
        $path = $this->root.'/bootstrap/providers.php';
        $class = "Modules\\{$module}\\Providers\\{$module}ServiceProvider";
        $useStatement = "use {$class};";

        $lines = explode("\n", $this->files->get($path));

        if (in_array($useStatement, array_map('trim', $lines), true)) {
            return $path;
        }

        $lines = $this->insertUseStatement($lines, $class);
        $lines = $this->insertProviderEntry($lines, $module);

        $this->files->put($path, implode("\n", $lines));

        return $path;
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function insertUseStatement(array $lines, string $class): array
    {
        $returnIndex = $this->providerListIndex($lines);
        $lastUseIndex = null;
        $insertBefore = null;

        for ($index = 0; $index < $returnIndex; $index++) {
            $trimmed = trim($lines[$index]);

            if (! str_starts_with($trimmed, 'use ')) {
                continue;
            }

            $lastUseIndex = $index;
            $imported = rtrim(Str::after($trimmed, 'use '), ';');

            if ($insertBefore === null && strcmp($imported, $class) > 0) {
                $insertBefore = $index;
            }
        }

        if ($insertBefore === null && $lastUseIndex === null) {
            throw new RuntimeException('Unable to locate a use statement in bootstrap/providers.php.');
        }

        array_splice($lines, $insertBefore ?? $lastUseIndex + 1, 0, ["use {$class};"]);

        return $lines;
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function insertProviderEntry(array $lines, string $module): array
    {
        $returnIndex = $this->providerListIndex($lines);
        $shortName = "{$module}ServiceProvider";
        $closingIndex = null;
        $insertBefore = null;

        for ($index = $returnIndex; $index < count($lines); $index++) {
            $trimmed = trim($lines[$index]);

            if ($trimmed === '];') {
                $closingIndex = $index;

                break;
            }

            if ($insertBefore === null
                && preg_match('/^([A-Za-z0-9_]+)::class,$/', $trimmed, $matches) === 1
                && strcmp($matches[1], $shortName) > 0) {
                $insertBefore = $index;
            }
        }

        if ($closingIndex === null) {
            throw new RuntimeException('Unable to locate the provider list in bootstrap/providers.php.');
        }

        array_splice($lines, $insertBefore ?? $closingIndex, 0, ["    {$shortName}::class,"]);

        return $lines;
    }

    /**
     * @param  list<string>  $lines
     */
    private function providerListIndex(array $lines): int
    {
        foreach ($lines as $index => $line) {
            if (str_starts_with(ltrim($line), 'return [')) {
                return $index;
            }
        }

        throw new RuntimeException('Unable to locate the provider list in bootstrap/providers.php.');
    }

    private function write(string $path, string $contents): string
    {
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);

        return $path;
    }

    private function providerStub(string $module): string
    {
        $stub = <<<'PHP'
<?php

namespace Modules\{{module}}\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class {{module}}ServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(__DIR__.'/../../routes/web.php');
        }
    }
}
PHP;

        return strtr($stub, ['{{module}}' => $module])."\n";
    }

    private function routesStub(string $module): string
    {
        return <<<PHP
<?php

// {$module} routes. Loaded inside the "web" middleware group by {$module}ServiceProvider.
PHP."\n";
    }

    private function contractStub(string $module, string $contract): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Contracts;

/**
 * Public API of the {$module} module.
 *
 * Declare only the methods the consuming module needs, and return Contracts\\Dto
 * objects instead of {$module} models.
 */
interface {$contract} {}
PHP."\n";
    }

    private function dtoStub(string $module, string $dto): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Contracts\\Dto;

/**
 * Data crossing the {$module} module boundary.
 *
 * Expose only the fields consumers need, never an Eloquent model.
 */
final readonly class {$dto} {}
PHP."\n";
    }
}
