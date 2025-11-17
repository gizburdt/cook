<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;

class Health extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:health {--force}';

    protected $description = 'Install Health';

    protected string $docs = 'https://spatie.be/docs/laravel-health/v1/introduction';

    protected array $packages = [
        'spatie/cpu-load-health-check' => 'require',
        'spatie/laravel-health' => 'require',
        'spatie/security-advisories-health-check' => 'require',
    ];

    public function handle(): void
    {
        $this->components->info('Publishing files');

        $this->call('vendor:publish', [
            '--tag' => 'cook-health',
            '--force' => $this->option('force'),
        ]);

        $this->components->info('Installing packages');

        $this->installPackages($this->packages);

        $this->openDocs();
    }
}
