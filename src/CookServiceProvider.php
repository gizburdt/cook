<?php

namespace Gizburdt\Cook;

use Gizburdt\Cook\Commands\Ai;
use Gizburdt\Cook\Commands\Backups;
use Gizburdt\Cook\Commands\BaseClasses;
use Gizburdt\Cook\Commands\CodeQuality;
use Gizburdt\Cook\Commands\Filament;
use Gizburdt\Cook\Commands\Install;
use Gizburdt\Cook\Commands\Packages;
use Gizburdt\Cook\Commands\Ui;
use Illuminate\Support\ServiceProvider;

class CookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            Install::class,
            BaseClasses::class,
            CodeQuality::class,
            Ai::class,
            Filament::class,
            Ui::class,
            Packages::class,
            Backups::class,
        ]);

        $this->publishes($this->baseClasses(), 'cook-base-classes');

        $this->publishes($this->codeQuality(), 'cook-code-quality');

        $this->publishes($this->ai(), 'cook-ai');

        $this->publishes($this->filament(), 'cook-filament');
    }

    protected function baseClasses(): array
    {
        return $this->files([
            'stubs' => 'stubs',
            'Http/Resources/Resource.php' => 'app/Http/Resources/Resource.php',
            'Models/Model.php' => 'app/Models/Model.php',
            'Models/Pivot.php' => 'app/Models/Pivot.php',
            'Models/Concerns' => 'app/Models/Concerns',
            'Policies/Policy.php' => 'app/Policies/Policy.php',
        ], 'base-classes');
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
        ], 'code-quality');
    }

    protected function ai(): array
    {
        return $this->files([
            '.ai' => '.ai',
            '.claude' => '.claude',
        ], 'ai');
    }

    protected function filament(): array
    {
        return $this->files([
            'Filament' => 'app/Filament',
        ], 'filament');
    }

    protected function files(array $files, string $group): array
    {
        return collect($files)->mapWithKeys(function ($value, $key) use ($group) {
            return [__DIR__."/../publish/{$group}/{$key}" => base_path($value)];
        })->toArray();
    }
}
