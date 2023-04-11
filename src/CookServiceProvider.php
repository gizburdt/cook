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
        ])->mapWithKeys(function ($value, $key) {
            return [__DIR__."/../publish/{$key}" => base_path($value)];
        })->toArray();
    }

    protected function publishStubs(): array
    {
        return collect([
            'stubs/controller.api.stub' => 'stubs/controller.api.stub',
            'stubs/controller.invokable.stub' => 'stubs/controller.invokable.stub',
            'stubs/controller.model.api.stub' => 'stubs/controller.model.api.stub',
            'stubs/controller.model.stub' => 'stubs/controller.model.stub',
            'stubs/controller.plain.stub' => 'stubs/controller.plain.stub',
            'stubs/controller.singleton.api.stub' => 'stubs/controller.singleton.api.stub',
            'stubs/controller.singleton.stub' => 'stubs/controller.singleton.stub',
            'stubs/controller.stub' => 'stubs/controller.stub',
            'stubs/migration.create.stub' => 'stubs/migration.create.stub',
            'stubs/model.stub' => 'stubs/model.stub',
            'stubs/observer.stub' => 'stubs/observer.stub',
            'stubs/policy.plain.stub' => 'stubs/policy.plain.stub',
            'stubs/policy.stub' => 'stubs/policy.stub',
            'stubs/resource.stub' => 'stubs/resource.stub',
        ])->mapWithKeys(function ($value, $key) {
            return [__DIR__."/../publish/{$key}" => base_path($value)];
        })->toArray();
    }
}
