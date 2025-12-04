<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;

class Ai extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:ai {--force}';

    protected $description = 'Install AI';

    protected array $packages = [
        'laravel/boost' => 'dev',
    ];

    public function handle(): void
    {
        $this->components->info('Publishing files');

        $this->call('vendor:publish', [
            '--tag' => 'cook-ai',
            '--force' => $this->option('force'),
        ]);

        if ($this->hasInstallablePackages($this->packages)) {
            $this->components->info('Installing packages');

            $this->installPackages($this->packages);
        }

        $this->call('boost:install');
    }
}
