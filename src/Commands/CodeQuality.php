<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;

class CodeQuality extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:code-quality {--force}';

    protected $description = 'Install Essentials, PHPstan, Pint, Rector, GitHub Actions';

    protected array $packages = [
        'canvural/larastan-strict-rules' => 'dev',
        'driftingly/rector-laravel' => 'dev',
        'larastan/larastan' => 'dev',
        'nunomaduro/essentials' => 'require',
        'nunomaduro/phpinsights' => 'dev',
        'pestphp/pest' => 'dev',
        'pestphp/pest-plugin-browser' => 'dev',
        'pestphp/pest-plugin-laravel' => 'dev',
        'pestphp/pest-plugin-livewire' => 'dev',
        'spatie/pest-plugin-test-time' => 'dev',
        'rector/rector' => 'dev',
    ];

    public function handle(): void
    {
        $this->components->info('Publishing files');

        $this->call('vendor:publish', [
            '--tag' => 'cook-code-quality',
            '--force' => $this->option('force'),
        ]);

        if ($this->hasInstallablePackages($this->packages)) {
            $this->components->info('Installing packages');

            $this->installPackages($this->packages);
        }
    }
}
