<?php

namespace App\Providers;

use App\Support\ModuleScaffolder;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ModuleScaffolder::class, fn (Application $app): ModuleScaffolder => new ModuleScaffolder(
            new Filesystem,
            $app->basePath(),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        $this->configureUrlGeneration();

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Pin every generated absolute URL to the configured application origin.
     *
     * Without a forced root, Laravel builds absolute URLs — including password
     * reset and email verification links — from the request's Host header, so a
     * caller-supplied Host could place a valid token on a foreign origin.
     */
    protected function configureUrlGeneration(): void
    {
        URL::forceRootUrl((string) config('app.url'));

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
