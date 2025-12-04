<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddHealthChecks;
use Gizburdt\Cook\Commands\NodeVisitors\AddHealthRoute;
use Gizburdt\Cook\Commands\NodeVisitors\AddHealthSchedule;

class Health extends Command
{
    use InstallsPackages;
    use UsesPhpParser;

    protected $signature = 'cook:health {--force}';

    protected $description = 'Install Health';

    protected string $docs = 'https://spatie.be/docs/laravel-health/v1/introduction';

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

        if ($this->hasInstallablePackages($this->packages)) {
            $this->components->info('Installing packages');

            $this->installPackages($this->packages);
        }

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

        $this->openDocs();
    }

    protected function addRoutes(): void
    {
        $file = base_path('routes/web.php');

        $content = $this->files->get($file);

        $content = $this->parseContent($content, [
            AddHealthRoute::class,
        ]);

        $this->files->put($file, $content);
    }

    protected function addSchedule(): void
    {
        $file = base_path('routes/console.php');

        $content = $this->files->get($file);

        $content = $this->parseContent($content, [
            AddHealthSchedule::class,
        ]);

        $this->files->put($file, $content);
    }

    protected function addChecks(): void
    {
        $file = app_path('Providers/AppServiceProvider.php');

        $content = $this->files->get($file);

        $content = $this->parseContent($content, [
            AddHealthChecks::class,
        ]);

        $this->files->put($file, $content);
    }
}
