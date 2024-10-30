<?php

namespace Gizburdt\Cook;

use Gizburdt\Cook\Commands\Install;
use Gizburdt\Cook\Commands\Model;
use Gizburdt\Cook\Commands\Packages;
use Gizburdt\Cook\Commands\Publish;
use Illuminate\Support\ServiceProvider;

class CookServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Install::class,
                Publish::class,
                Model::class,
                Packages::class,
            ]);
        }

        // Publishes
        if ($this->app->runningInConsole()) {
            $this->publishes($this->publishFiles(), 'cook-files');

            $this->publishes($this->publishStubs(), 'cook-stubs');
        }
    }

    public function register()
    {
        //
    }

    protected function publishFiles(): array
    {
        return collect([
            'Http/Resources/Resource.php' => 'app/Http/Resources/Resource.php',
            'Models/Model.php' => 'app/Models/Model.php',
            'Policies/Policy.php' => 'app/Policies/Policy.php',
            'pint.json' => 'pint.json',
            'phpstan.neon' => 'phpstan.neon',
            '.github' => '.github',
        ])->mapWithKeys(function ($value, $key) {
            return [__DIR__."/../publish/{$key}" => base_path($value)];
        })->toArray();
    }

    protected function publishStubs(): array
    {
        return collect([
            'stubs' => 'stubs',
        ])->mapWithKeys(function ($value, $key) {
            return [__DIR__."/../publish/{$key}" => base_path($value)];
        })->toArray();
    }
}
