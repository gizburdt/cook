<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\InstallsPassport;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddPassportAuthorizationView;

class Mcp extends Command
{
    use InstallsPackages;
    use InstallsPassport;
    use UsesPhpParser;

    protected $signature = 'cook:mcp {--force} {--skip-pint}';

    protected $description = 'Install MCP';

    protected string $docs = 'https://laravel.com/docs/12.x/mcp';

    public string $publishGroup = 'mcp';

    public array $publishes = [
        'routes/ai.php' => 'routes/ai.php',
        'Mcp' => 'app/Mcp',
        'resources/views/mcp/authorize.blade.php' => 'resources/views/mcp/authorize.blade.php',
    ];

    protected array $packages = [
        'laravel/mcp' => 'require',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-mcp',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->installPassport();

        $this->addCode();

        $this->runPint();

        $this->openDocs();
    }

    protected function addCode(): void
    {
        $this->applyPhpVisitors(app_path('Providers/AppServiceProvider.php'), [
            AddPassportAuthorizationView::class,
        ]);
    }
}
