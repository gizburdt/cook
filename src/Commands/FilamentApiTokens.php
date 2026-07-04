<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\InstallsPassport;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddApiTokensUserMenuItem;

class FilamentApiTokens extends Command
{
    use InstallsPackages;
    use InstallsPassport;
    use UsesPhpParser;

    protected $signature = 'cook:filament:api-tokens {--force} {--skip-pint}';

    protected $description = 'Install Filament Api Token manager';

    public string $publishGroup = 'filament-api-tokens';

    public array $publishes = [
        'Filament/Pages/ApiTokens.php' => 'app/Filament/Pages/ApiTokens.php',
        'resources/views/filament/pages/api-tokens.blade.php' => 'resources/views/filament/pages/api-tokens.blade.php',
    ];

    protected array $packages = [
        //
    ];

    public function handle(): void
    {
        $this->installPassport();

        $this->call('vendor:publish', [
            '--tag' => 'cook-filament-api-tokens',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->addCode();
    }

    protected function addCode(): void
    {
        $this->applyPhpVisitors('app/Providers/Filament/AdminPanelProvider.php', [
            AddApiTokensUserMenuItem::class,
        ]);
    }
}
