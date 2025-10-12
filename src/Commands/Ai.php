<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Composer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class Ai extends Command
{
    protected $signature = 'cook:code-quality {--force}';

    protected $description = 'Install Essentials, PHPstan, Pint, Rector, GitHub Actions';

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

        $this->components->info('Installing packages');

        $this->installPackages($this->packages);
    }
}
