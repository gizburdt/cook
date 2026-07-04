<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\InstallsPassport;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddAccessTokensUserMenuItem;

class FilamentAccessTokens extends Command
{
    use InstallsPackages;
    use InstallsPassport;
    use UsesPhpParser;

    protected $signature = 'cook:filament:access-tokens {--force} {--skip-pint}';

    protected $description = 'Install Filament Access Token manager';

    public string $publishGroup = 'filament-access-tokens';

    public array $publishes = [
        'Filament/Pages/AccessTokens.php' => 'app/Filament/Pages/AccessTokens.php',
        'resources/views/filament/pages/access-tokens.blade.php' => 'resources/views/filament/pages/access-tokens.blade.php',
    ];

    protected array $packages = [
        //
    ];

    public function handle(): void
    {
        $this->installPassport();

        $this->call('vendor:publish', [
            '--tag' => 'cook-filament-access-tokens',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->addCode();
    }

    protected function addCode(): void
    {
        $this->applyPhpVisitors('app/Providers/Filament/AdminPanelProvider.php', [
            AddAccessTokensUserMenuItem::class,
        ]);
    }
}
