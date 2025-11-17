<?php

namespace Gizburdt\Cook;

use Gizburdt\Cook\Commands\Ai;
use Gizburdt\Cook\Commands\Backups;
use Gizburdt\Cook\Commands\BaseClasses;
use Gizburdt\Cook\Commands\CodeQuality;
use Gizburdt\Cook\Commands\Filament;
use Gizburdt\Cook\Commands\Install;
use Gizburdt\Cook\Commands\Packages;
use Gizburdt\Cook\Commands\Stubs;
use Gizburdt\Cook\Commands\Ui;
use Illuminate\Support\ServiceProvider;

class CookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            Install::class,
            Stubs::class,
            BaseClasses::class,
            CodeQuality::class,
            Ai::class,
            Filament::class,
            Ui::class,
            Packages::class,
            Backups::class,
        ]);

        $this->publishes($this->stubs(), 'cook-stubs');

        $this->publishes($this->baseClasses(), 'cook-base-classes');

        $this->publishes($this->codeQuality(), 'cook-code-quality');

        $this->publishes($this->ai(), 'cook-ai');

        $this->publishes($this->filament(), 'cook-filament');

        $this->publishes($this->health(), 'cook-health');
    }

    protected function stubs(): array
    {
        return $this->files([
            'stubs' => 'stubs',
        ]);
    }

    protected function baseClasses(): array
    {
        return $this->files([
            'Http/Resources/Resource.php' => 'app/Http/Resources/Resource.php',
            'Models/Model.php' => 'app/Models/Model.php',
            'Models/Pivot.php' => 'app/Models/Pivot.php',
            'Policies/Policy.php' => 'app/Policies/Policy.php',
        ]);
    }

    protected function codeQuality(): array
    {
        return $this->files([
            '.github' => '.github',
            'config/essentials.php' => 'config/essentials.php',
            'config/insights.php' => 'config/insights.php',
            'phpstan.neon' => 'phpstan.neon',
            'pint.json' => 'pint.json',
            'rector.php' => 'rector.php',
        ]);
    }

    protected function ai(): array
    {
        return $this->files([
            '.ai' => '.ai',
            '.claude' => '.claude',
        ]);
    }

    protected function filament(): array
    {
        return $this->files([
            'Filament' => 'app/Filament',
        ]);
    }

    protected function health(): array
    {
        return $this->files([
            'config/health.php' => 'config/health.php',
        ]);
    }

    protected function files(array $files): array
    {
        return collect($files)->mapWithKeys(function ($value, $key) {
            return [__DIR__."/../publish/{$key}" => base_path($value)];
        })->toArray();
    }
}
