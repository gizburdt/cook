<?php

namespace Gizburdt\Cook;

use Gizburdt\Cook\Commands\Install;
use Gizburdt\Cook\Commands\Model;
use Gizburdt\Cook\Commands\Packages;
use Gizburdt\Cook\Commands\Publish;
use Illuminate\Support\ServiceProvider;

class CookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            Install::class,
            Publish::class,
            Model::class,
            Packages::class,
        ]);

        $this->publishes($this->filesToPublish(), 'cook-files');

        $this->publishes($this->stubsToPublish(), 'cook-stubs');
    }

    protected function filesToPublish(): array
    {
        return collect([
            'Http/Resources/Resource.php' => 'app/Http/Resources/Resource.php',
            'Models/Model.php' => 'app/Models/Model.php',
            'Policies/Policy.php' => 'app/Policies/Policy.php',
            'config/insights.php' => 'config/insights.php',
            'rector.php' => 'rector.php',
            'pint.json' => 'pint.json',
            'phpstan.neon' => 'phpstan.neon',
            '.github' => '.github',
            '.ai' => '.ai',
        ])->mapWithKeys(function ($value, $key) {
            return [__DIR__."/../publish/{$key}" => base_path($value)];
        })->toArray();
    }

    protected function stubsToPublish(): array
    {
        return collect([
            'stubs' => 'stubs',
        ])->mapWithKeys(function ($value, $key) {
            return [__DIR__."/../publish/{$key}" => base_path($value)];
        })->toArray();
    }
}
