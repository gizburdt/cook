<?php

namespace Gizburdt\Cook\Commands;

use function Laravel\Prompts\confirm;

class Install extends Command
{
    protected $signature = 'cook:install {--force}';

    protected $description = 'Install';

    public function handle(): void
    {
        $arguments = ['--force' => $this->option('force')];

        // Base
        if (confirm(label: 'Install base?', hint: 'Recommended on new installation.')) {
            $this->call('cook:base', $arguments);
        }

        // AI
        if (confirm(label: 'Install ai?')) {
            $this->call('cook:ai', $arguments);
        }

        // Code quality
        if (confirm(label: 'Install code quality?')) {
            $this->call('cook:code-quality', $arguments);
        }

        // Operations
        if (confirm(label: 'Install operations?')) {
            $this->call('cook:operations', $arguments);
        }

        // Health
        if (confirm(label: 'Run cook:health?')) {
            $this->call('cook:health', $arguments);
        }

        // Failed job monitor
        if (confirm(label: 'Run cook:failed-job-monitor?')) {
            $this->call('cook:failed-job-monitor', $arguments);
        }

        // Backups
        if (confirm(label: 'Run cook:backups?')) {
            $this->call('cook:backups', $arguments);
        }

        // Filament
        if (confirm(label: 'Install Filament?')) {
            $this->call('cook:filament', $arguments);
        }

        // UI
        if (confirm(label: 'Install UI?')) {
            $this->call('cook:ui', $arguments);
        }

        // Packages
        if (confirm(label: 'Install extra packages?')) {
            $this->call('cook:packages', $arguments);
        }
    }
}
