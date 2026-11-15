<?php

namespace App\Console\Commands;

use App\Support\ModuleScaffolder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('make:contract
    {module : The existing module that owns the contract, for example "Identity"}
    {name : The contract name, for example "UserDirectory"}
    {--dto= : Also scaffold a readonly DTO in Contracts/Dto}')]
#[Description('Create a module contract for a consumer that already exists')]
class MakeContractCommand extends Command
{
    public function handle(ModuleScaffolder $scaffolder): int
    {
        $module = Str::studly(trim((string) $this->argument('module')));
        $contract = Str::studly(trim((string) $this->argument('name')));

        if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $contract)) {
            $this->components->error('The contract name must be a single StudlyCase word, for example "UserDirectory".');

            return self::FAILURE;
        }

        $dto = $this->option('dto');
        $dto = is_string($dto) && trim($dto) !== '' ? Str::studly(trim($dto)) : null;

        if ($dto !== null && ! preg_match('/^[A-Z][A-Za-z0-9]*$/', $dto)) {
            $this->components->error('The DTO name must be a single StudlyCase word, for example "UserSummary".');

            return self::FAILURE;
        }

        if (! $scaffolder->exists($module)) {
            $this->components->error("Module [{$module}] does not exist. Run \"php artisan make:module {$module}\" first.");

            return self::FAILURE;
        }

        if ($scaffolder->contractExists($module, $contract)) {
            $this->components->error("Contract [Modules\\{$module}\\Contracts\\{$contract}] already exists.");

            return self::FAILURE;
        }

        foreach ($scaffolder->scaffoldContract($module, $contract, $dto) as $path) {
            $this->components->twoColumnDetail(
                Str::after($path, $scaffolder->rootPath().DIRECTORY_SEPARATOR),
                '<fg=green;options=bold>CREATED</>',
            );
        }

        $this->components->info("Contract [Modules\\{$module}\\Contracts\\{$contract}] created for its first consumer.");

        $this->components->bulletList([
            "Declare only the methods the consuming module needs on {$contract}.",
            "Implement it in the {$module} module and bind it in {$module}ServiceProvider::register().",
            'tests/Arch/ModuleBoundariesTest.php enforces the Contracts boundary from now on.',
        ]);

        return self::SUCCESS;
    }
}
