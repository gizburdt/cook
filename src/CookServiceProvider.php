<?php

namespace Gizburdt\Cook;

use Gizburdt\Cook\Commands\AuthJson;
use Gizburdt\Cook\Commands\DocBlocks;
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
                Publish::class,
                Install::class,
                //
                AuthJson::class,
                DocBlocks::class,
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
            'Http/Controllers/Controller.php' => 'app/Http/Controllers/Controller.php',
            'Http/Resources/Resource.php' => 'app/Http/Resources/Resource.php',
            'Models/Model.php' => 'app/Models/Model.php',
            'Nova/Resource.php' => 'app/Nova/Resource.php',
            'draft.yaml' => 'draft.yaml',
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
