<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddAccessTokensUserMenuItem;

use function Laravel\Prompts\select;

class FilamentApiTokens extends Command
{
    use InstallsPackages;
    use UsesPhpParser;

    protected $signature = 'cook:filament:access-tokens {--force} {--skip-pint}';

    protected $description = 'Install Filament access Tokens';

    protected string $driver;

    public string $publishGroup = 'filament-access-tokens';

    public array $publishes = [
        'sanctum/Filament/Pages/AccessTokens.php' => 'app/Filament/Pages/AccessTokens.php',
        'sanctum/resources/views/filament/pages/access-tokens.blade.php' => 'resources/views/filament/pages/access-tokens.blade.php',
    ];

    protected array $packages = [
        //
    ];

    public function handle(): void
    {
        $this->driver = select('Which authentication?', [
            'sanctum' => 'Sanctum',
        ]);

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
