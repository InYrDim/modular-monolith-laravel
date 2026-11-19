<?php

namespace Modules\Billing\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
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
