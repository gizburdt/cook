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

        $this->publishes($this->filesToPublish(), 'cook');
    }

    protected function filesToPublish(): array
    {
        return collect([
            '.ai' => '.ai',
            '.github' => '.github',
            'config/insights.php' => 'config/insights.php',
            'Http/Resources/Resource.php' => 'app/Http/Resources/Resource.php',
            'Models/Model.php' => 'app/Models/Model.php',
            'Policies/Policy.php' => 'app/Policies/Policy.php',
            'stubs' => 'stubs',
            'phpstan.neon' => 'phpstan.neon',
            'pint.json' => 'pint.json',
            'rector.php' => 'rector.php',
        ])->mapWithKeys(function ($value, $key) {
            return [__DIR__."/../publish/{$key}" => base_path($value)];
        })->toArray();
    }
}
