<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddSanctumHasApiTokens;

use function Laravel\Prompts\select;

class Mcp extends Command
{
    use InstallsPackages;
    use UsesPhpParser;

    protected $signature = 'cook:mcp {--force} {--skip-pint}';

    protected $description = 'Install MCP';

    protected string $docs = 'https://laravel.com/docs/12.x/mcp';

    protected string $driver;

    public string $publishGroup = 'mcp';

    public array $publishes = [
        'routes/ai.php' => 'routes/ai.php',
        'Mcp' => 'app/Mcp',
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

        $this->installApi();

        $this->runPint();

        $this->openDocs();
    }

    protected function installApi(): void
    {
        $this->driver = select(__('Which authentication?'), [
            'passport' => 'Passport',
            'sanctum' => 'Sanctum',
        ], 'passport');

        match ($this->driver) {
            'passport' => $this->installPassport(),
            default => $this->installSanctum(),
        };
    }

    protected function installSanctum(): void
    {
        $this->components->info('Install Sanctum');

        $this->runInNewProcess('php artisan install:api --without-migration-prompt');

        $this->applyPhpVisitors(app_path('Models/User.php'), [
            AddSanctumHasApiTokens::class,
        ]);
    }

    protected function installPassport(): void
    {
        $this->components->info('Install Passport');

        $this->runInNewProcess('php artisan install:api --passport');

        $this->applyPhpVisitors(app_path('Models/User.php'), [
            // AddSanctumHasApiTokens::class,
        ]);

        // guards in config/auth.php
        // 'api' => [
        //     'driver' => 'passport',
        //     'provider' => 'users',
        // ],

        // php artisan vendor:publish --tag=mcp-views
        // gebruik custom view ivm shadcn

        // ServiceProvider
        // Passport::authorizationView(function ($parameters) {
        //     return view('mcp.authorize', $parameters);
        // });

        // routes/ai.php
        // Mcp::oauthRoutes();
    }
}
