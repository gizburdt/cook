<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddUserMenuItems;

use function Laravel\Prompts\select;

class FilamentApiTokens extends Command
{
    use InstallsPackages;
    use UsesPhpParser;

    protected $signature = 'cook:filament:api-tokens {--force} {--skip-pint}';

    protected $description = 'Install Filament Api Token manager';

    protected string $driver;

    public string $publishGroup = 'filament-api-tokens';

    public array $publishes = [
        'sanctum/Filament/Pages/ApiTokens.php' => 'app/Filament/Pages/ApiTokens.php',
        'sanctum/resources/views/filament/pages/api-tokens.blade.php' => 'resources/views/filament/pages/api-tokens.blade.php',
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
            '--tag' => 'cook-filament-api-tokens',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->addCode();
    }

    protected function addCode(): void
    {
        $this->applyPhpVisitors('app/Providers/Filament/AdminPanelProvider.php', [
            AddUserMenuItems::class,
        ]);
    }
}
