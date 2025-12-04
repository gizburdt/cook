<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;

use function Laravel\Prompts\confirm;

class Install extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:install {--force}';

    protected $description = 'Install';

    protected array $packages = [
        'barryvdh/laravel-debugbar' => 'dev',
        'laracasts/presenter' => 'require',
        'laravel/horizon' => 'require',
        'laravel/pail' => 'dev',
        'laravel/prompts' => 'require',
        'lorisleiva/laravel-actions' => 'require',
        'spatie/laravel-failed-job-monitor' => 'require',
        'spatie/laravel-ray' => 'require',
        'timokoerber/laravel-one-time-operations' => 'require',
    ];

    public function handle(): void
    {
        $this->core();

        $arguments = ['--force' => $this->option('force')];

        // Stubs
        if (confirm(label: 'Install stubs?')) {
            $this->call('cook:stubs', $arguments);
        }

        // Base classes
        if (confirm(label: 'Install base classes?')) {
            $this->call('cook:base-classes', $arguments);
        }

        // Code quality
        if (confirm(label: 'Install code quality?')) {
            $this->call('cook:code-quality', $arguments);
        }

        // AI
        if (confirm(label: 'Install ai?')) {
            $this->call('cook:ai', $arguments);
        }

        // Filament
        if (confirm(label: 'Install Filament?')) {
            $this->call('cook:filament', $arguments);
        }

        // Backups
        // if (confirm(label: 'Run cook:backups?')) {
        //     $this->call('cook:backups', $arguments);
        // }

        // UI
        if (confirm(label: 'Install UI?')) {
            $this->call('cook:ui', $arguments);
        }

        // Packages
        if (confirm(label: 'Install extra packages?')) {
            $this->call('cook:packages', $arguments);
        }
    }

    protected function core(): void
    {
        if ($this->hasInstallablePackages($this->packages)) {
            $this->components->info('Installing packages');

            $this->installPackages($this->packages);
        }
    }
}
