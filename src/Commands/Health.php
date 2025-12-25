<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesEnvParser;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddHealthChecks;
use Gizburdt\Cook\Commands\NodeVisitors\AddHealthRoute;
use Gizburdt\Cook\Commands\NodeVisitors\AddHealthSchedule;

class Health extends Command
{
    use InstallsPackages;
    use UsesEnvParser;
    use UsesPhpParser;

    protected $signature = 'cook:health {--force} {--skip-pint}';

    protected $description = 'Install Health';

    protected string $docs = 'https://spatie.be/docs/laravel-health/v1/introduction';

    public string $publishGroup = 'health';

    public array $publishes = [
        'config/health.php' => 'config/health.php',
        'Support/Notifiable.php' => 'app/Support/Health/Notifiable.php',
        'Support/Notification.php' => 'app/Support/Health/Notification.php',
    ];

    protected array $packages = [
        'awssat/discord-notification-channel' => 'require',
        'doctrine/dbal' => 'require',
        'spatie/cpu-load-health-check' => 'require',
        'spatie/laravel-health' => 'require',
        'spatie/security-advisories-health-check' => 'require',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-health',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->addCode();
    }

    protected function addCode(): void
    {
        $this->components->info('Adding checks');

        $this->addChecks();

        $this->components->info('Adding route');

        $this->addRoutes();

        $this->components->info('Adding schedule');

        $this->addSchedule();

        $this->components->info('Adding environment variables');

        $this->addEnvVariables([
            'HEALTH_DISCORD_WEBHOOK_URL' => '',
        ]);

        $this->runPint();

        $this->openDocs();
    }

    protected function addRoutes(): void
    {
        $this->applyPhpVisitors(base_path('routes/web.php'), [
            AddHealthRoute::class,
        ]);
    }

    protected function addSchedule(): void
    {
        $this->applyPhpVisitors(base_path('routes/console.php'), [
            AddHealthSchedule::class,
        ]);
    }

    protected function addChecks(): void
    {
        $this->applyPhpVisitors(app_path('Providers/AppServiceProvider.php'), [
            AddHealthChecks::class,
        ]);
    }
}
