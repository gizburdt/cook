<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;

class Ai extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:ai {--force} {--skip-pint}';

    protected $description = 'Install AI';

    protected array $packages = [
        'laravel/boost' => 'dev',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-ai',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->callInNewProcess('boost:install');

        $this->components->info('Updating composer.json');

        $this->composer->addScript('post-update-cmd', '@php artisan boost:update --ansi');

        $this->runPint();
    }
}
