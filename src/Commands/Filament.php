<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;

class Filament extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:filament {--force}';

    protected $description = 'Install Filament';

    protected array $packages = [
        'filament/filament' => 'require',
    ];

    public function handle(): void
    {
        $this->components->info('Publishing files');

        $this->call('vendor:publish', [
            '--tag' => 'cook-filament',
            '--force' => $this->option('force'),
        ]);

        $this->components->info('Installing packages');

        $this->installPackages($this->packages);
    }
}
