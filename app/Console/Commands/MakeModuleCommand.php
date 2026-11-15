<?php

namespace App\Console\Commands;

use App\Support\ModuleScaffolder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;

#[Signature('make:module
    {name : The module name, for example "Billing"}
    {--api : Also scaffold routes/api.php for the module}
    {--no-autoload : Skip regenerating the Composer autoloader}')]
#[Description('Create a new module and register it with the host application')]
class MakeModuleCommand extends Command
{
    public function handle(ModuleScaffolder $scaffolder, Filesystem $files): int
    {
        $module = Str::studly(trim((string) $this->argument('name')));

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $module)) {
            $this->components->error('The module name must be a single StudlyCase word, for example "Billing".');

            return self::FAILURE;
        }

        if ($scaffolder->exists($module)) {
            $this->components->error("Module [{$module}] already exists.");

            return self::FAILURE;
        }

        foreach ($scaffolder->scaffold($module, withApiRoutes: (bool) $this->option('api')) as $path) {
            $this->components->twoColumnDetail(
                Str::after($path, $scaffolder->rootPath().DIRECTORY_SEPARATOR),
                '<fg=green;options=bold>CREATED</>',
            );
        }

        if (! $this->option('no-autoload')) {
            $exitCode = (new Composer($files, $scaffolder->rootPath()))->dumpAutoloads();

            if ($exitCode !== 0) {
                $this->components->error('Composer could not regenerate the autoloader. Run "composer dump-autoload" manually.');

                return self::FAILURE;
            }
        }

        $this->components->info("Module [{$module}] created. Add code under modules/{$module}/src.");

        return self::SUCCESS;
    }
}
