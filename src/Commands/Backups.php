<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;

class Backups extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:backups {--force}';

    protected $description = 'Install backups';

    protected array $packages = [
        'awssat/discord-notification-channel' => 'require',
        'spatie/laravel-backup' => 'require',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-backups',
            '--force' => $this->option('force'),
        ]);

        if ($this->hasInstallablePackages($this->packages)) {
            $this->components->info('Installing packages');

            $this->installPackages($this->packages);
        }
    }
}
