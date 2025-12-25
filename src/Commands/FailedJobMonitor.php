<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesEnvParser;

class FailedJobMonitor extends Command
{
    use InstallsPackages;
    use UsesEnvParser;

    protected $signature = 'cook:failed-job-monitor {--force} {--skip-pint}';

    protected $description = 'Install Failed Job Monitor';

    public string $publishGroup = 'failed-job-monitor';

    public array $publishes = [
        'config/failed-job-monitor.php' => 'config/failed-job-monitor.php',
        'Support/Notifiable.php' => 'app/Support/FailedJobMonitor/Notifiable.php',
        'Support/Notification.php' => 'app/Support/FailedJobMonitor/Notification.php',
    ];

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

        $this->tryInstallPackages();

        $this->components->info('Adding environment variables');

        $this->addEnvVariables([
            'FAILED_JOB_DISCORD_WEBHOOK_URL' => '',
        ]);

        $this->runPint();
    }
}
