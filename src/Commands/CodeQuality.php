<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;

class CodeQuality extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:code-quality {--force} {--skip-pint}';

    protected $description = 'Install Essentials, PHPstan, Pint, Rector, GitHub Actions';

    public string $publishGroup = 'code-quality';

    public array $publishes = [
        '.github' => '.github',
        'config/essentials.php' => 'config/essentials.php',
        'config/insights.php' => 'config/insights.php',
        'phpstan.neon' => 'phpstan.neon',
        'pint.json' => 'pint.json',
        'rector.php' => 'rector.php',
    ];

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
        $this->call('vendor:publish', [
            '--tag' => 'cook-code-quality',
            '--force' => $this->option('force'),
        ]);

        $this->components->info('Updating composer.json');

        $this->composer->allowPlugin('dealerdirect/phpcodesniffer-composer-installer');

        $this->tryInstallPackages();

        $this->runPint();
    }
}
