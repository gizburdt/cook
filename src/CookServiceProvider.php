<?php

namespace Gizburdt\Cook;

use Gizburdt\Cook\Commands\AuthJson;
use Gizburdt\Cook\Commands\NovaResource;
use Gizburdt\Cook\Commands\Packages;
use Illuminate\Support\ServiceProvider;

class CookServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AuthJson::class,
                NovaResource::class,
                Packages::class,
            ]);
        }

        // Publishes
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/cook.php' => config_path('cook.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cook.php', 'cook');
    }
}
