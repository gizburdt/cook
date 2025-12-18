<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;

class Operations extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:operations {--force} {--skip-pint}';

    protected $description = 'Publish one-time-operations files';

    protected array $packages = [
        'timokoerber/laravel-one-time-operations' => 'require',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-operations',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->runPint();
    }
}
