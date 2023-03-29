<?php

namespace Gizburdt\Cook;

use Gizburdt\Cook\Commands\ApiResource;
use Gizburdt\Cook\Commands\AuthJson;
use Gizburdt\Cook\Commands\DocBlocks;
use Gizburdt\Cook\Commands\Model;
use Gizburdt\Cook\Commands\NovaResource;
use Gizburdt\Cook\Commands\Packages;
use Gizburdt\Cook\Commands\ShiftBlueprint;
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
                ApiResource::class,
                AuthJson::class,
                DocBlocks::class,
                Model::class,
                NovaResource::class,
                Packages::class,
                ShiftBlueprint::class,
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
