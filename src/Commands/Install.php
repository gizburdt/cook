<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Illuminate\Support\Collection;

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
        'lorisleiva/laravel-actions' => 'require',
        'spatie/laravel-failed-job-monitor' => 'require',
        'spatie/laravel-ray' => 'require',
    ];

    public function handle(): void
    {
        $this->core();

        // Stubs
        if (confirm(label: 'Install stubs?')) {
            $this->call('cook:stubs');
        }

        // Base classes
        if (confirm(label: 'Install base classes?')) {
            $this->call('cook:base-classes');
        }

        // Code quality
        if (confirm(label: 'Install code quality?')) {
            $this->call('cook:base-classes');
        }

        // AI
        if (confirm(label: 'Install ai?')) {
            $this->call('cook:ai');
        }

        // Packages
        if (confirm(label: '', hint: 'Install packages?')) {
            $this->call('cook:packages');
        }

        // Backups
        // if (confirm(label: 'Run cook:backups?')) {
        //     $this->call('cook:backups');
        // }
    }

    protected function core(): void
    {
        $this->components->info('Installing packages');

        $this->installPackages($this->packages);
    }
}
