<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesEnvParser;

class FailedJobMonitor extends Command
{
    use InstallsPackages;
    use UsesEnvParser;

    protected $signature = 'cook:failed-job-monitor {--force}';

    protected $description = 'Install Failed Job Monitor';

    protected array $packages = [
        'awssat/discord-notification-channel' => 'require',
        'spatie/laravel-failed-job-monitor' => 'require',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-failed-job-monitor',
            '--force' => $this->option('force'),
        ]);

        if ($this->hasInstallablePackages($this->packages)) {
            $this->components->info('Installing packages');

            $this->installPackages($this->packages);
        }

        $this->components->info('Adding environment variables');

        $this->addEnvVariables([
            'FAILED_JOB_DISCORD_WEBHOOK_URL' => '',
        ]);
    }
}
