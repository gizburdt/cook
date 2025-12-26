<?php

namespace Gizburdt\Cook\Commands;

use function Laravel\Prompts\confirm;

class Install extends Command
{
    protected $signature = 'cook:install {--force}';

    protected $description = 'Install';

    public function handle(): void
    {
        $arguments = [
            '--force' => $this->option('force'),
            '--skip-pint' => true,
        ];

        // Base
        if (confirm('Install base?', hint: 'Recommended on new installation.')) {
            $this->call('cook:base', $arguments);
        }

        // AI
        if (confirm('Install ai?')) {
            $this->call('cook:ai', $arguments);
        }

        // Code quality
        if (confirm('Install code quality?')) {
            $this->call('cook:code-quality', $arguments);
        }

        // Operations
        if (confirm('Install operations?')) {
            $this->call('cook:operations', $arguments);
        }

        // Health
        if (confirm('Run cook:health?')) {
            $this->call('cook:health', $arguments);
        }

        // Failed job monitor
        if (confirm('Run cook:failed-job-monitor?')) {
            $this->call('cook:failed-job-monitor', $arguments);
        }

        // Backups
        if (confirm('Run cook:backups?')) {
            $this->call('cook:backups', $arguments);
        }

        // Filament
        if ($installFilament = confirm('Install Filament?')) {
            $this->call('cook:filament', $arguments);
        }

        // Filament panel
        if ($installFilament && confirm('Install Filament panel?', default: false)) {
            $this->call('cook:filament:panel', $arguments);
        }

        // UI
        if (confirm('Install UI?', default: false)) {
            $this->call('cook:ui', $arguments);
        }

        // Packages
        if (confirm('Install extra packages?', default: false)) {
            $this->call('cook:packages', $arguments);
        }

        $this->runPint();
    }
}
