<?php

namespace Gizburdt\Cook;

use Gizburdt\Cook\Commands\Ai;
use Gizburdt\Cook\Commands\Backups;
use Gizburdt\Cook\Commands\Base;
use Gizburdt\Cook\Commands\CodeQuality;
use Gizburdt\Cook\Commands\FailedJob;
use Gizburdt\Cook\Commands\FailedJobMonitor;
use Gizburdt\Cook\Commands\Filament;
use Gizburdt\Cook\Commands\FilamentPanel;
use Gizburdt\Cook\Commands\Health;
use Gizburdt\Cook\Commands\Install;
use Gizburdt\Cook\Commands\Operations;
use Gizburdt\Cook\Commands\Packages;
use Gizburdt\Cook\Commands\Ui;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class CookServiceProvider extends ServiceProvider
{
    protected array $commands = [
        Install::class,
        FailedJob::class,
        //
        Base::class,
        Ai::class,
        CodeQuality::class,
        Operations::class,
        Health::class,
        FailedJobMonitor::class,
        Backups::class,
        Filament::class,
        FilamentPanel::class,
        Ui::class,
        Packages::class,
    ];

    public function register(): void
    {
        $this->app->singleton(Composer::class, function ($app) {
            return new Composer($app->make(Filesystem::class), base_path());
        });
    }

    public function boot(): void
    {
        $this->commands($this->commands);

        $this->bootPublishes();
    }

    protected function bootPublishes(): void
    {
        foreach ($this->commands as $class) {
            $command = $this->app->make($class);

            if (empty($command->publishGroup)) {
                continue;
            }

            $files = $this->files($command->publishes, $command->publishGroup);

            $this->publishes($files, "cook-{$command->publishGroup}");
        }
    }

    protected function files(array $files, string $group): array
    {
        return collect($files)->mapWithKeys(function ($value, $key) use ($group) {
            return [__DIR__."/../publish/{$group}/{$key}" => base_path($value)];
        })->toArray();
    }
}
