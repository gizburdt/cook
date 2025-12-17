<?php

namespace Gizburdt\Cook;

use Gizburdt\Cook\Commands\Ai;
use Gizburdt\Cook\Commands\Backups;
use Gizburdt\Cook\Commands\Base;
use Gizburdt\Cook\Commands\CodeQuality;
use Gizburdt\Cook\Commands\FailedJob;
use Gizburdt\Cook\Commands\FailedJobMonitor;
use Gizburdt\Cook\Commands\Filament;
use Gizburdt\Cook\Commands\Health;
use Gizburdt\Cook\Commands\Install;
use Gizburdt\Cook\Commands\Operations;
use Gizburdt\Cook\Commands\Packages;
use Gizburdt\Cook\Commands\Ui;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class CookServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Composer::class, function ($app) {
            return new Composer($app->make(Filesystem::class), base_path());
        });
    }

    public function boot(): void
    {
        $this->commands([
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
            Ui::class,
            Packages::class,
        ]);

        $this->publishes($this->base(), 'cook-base');

        $this->publishes($this->ai(), 'cook-ai');

        $this->publishes($this->codeQuality(), 'cook-code-quality');

        $this->publishes($this->operations(), 'cook-operations');

        $this->publishes($this->health(), 'cook-health');

        $this->publishes($this->failedJobMonitor(), 'cook-failed-job-monitor');

        $this->publishes($this->backups(), 'cook-backups');

        $this->publishes($this->filament(), 'cook-filament');
    }

    protected function base(): array
    {
        return $this->files([
            'stubs' => 'stubs',
            'Actions/Action.php' => 'app/Actions/Action.php',
            'Http/Resources/Resource.php' => 'app/Http/Resources/Resource.php',
            'Models/Model.php' => 'app/Models/Model.php',
            'Models/Pivot.php' => 'app/Models/Pivot.php',
            'Models/Concerns' => 'app/Models/Concerns',
            'Policies/Policy.php' => 'app/Policies/Policy.php',
            'Support/helpers.php' => 'app/Support/helpers.php',
            'routes/local.php' => 'routes/local.php',
        ], 'base');
    }

    protected function ai(): array
    {
        return $this->files([
            '.ai' => '.ai',
            '.claude' => '.claude',
        ], 'ai');
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

    protected function operations(): array
    {
        return $this->files([
            'config/one-time-operations.php' => 'config/one-time-operations.php',
            'stubs/one-time-operation.stub' => 'stubs/one-time-operation.stub',
        ], 'operations');
    }

    protected function health(): array
    {
        return $this->files([
            'config/health.php' => 'config/health.php',
            'Support/Notifiable.php' => 'app/Support/Health/Notifiable.php',
            'Support/Notification.php' => 'app/Support/Health/Notification.php',
        ], 'health');
    }

    protected function failedJobMonitor(): array
    {
        return $this->files([
            'config/failed-job-monitor.php' => 'config/failed-job-monitor.php',
            'Support/Notifiable.php' => 'app/Support/FailedJobMonitor/Notifiable.php',
            'Support/Notification.php' => 'app/Support/FailedJobMonitor/Notification.php',
        ], 'failed-job-monitor');
    }

    protected function backups(): array
    {
        return $this->files([
            'config/backup.php' => 'config/backup.php',
        ], 'backups');
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
