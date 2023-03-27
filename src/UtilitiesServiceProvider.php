<?php

namespace Gizburdt\Cook;

use Gizburdt\Cook\Commands\Packages;
use Illuminate\Support\ServiceProvider;

class UtilitiesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Packages::class,
            ]);
        }

        // Publishes
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/utilities.php' => config_path('utilities.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/utilities.php', 'utilities');
    }
}
