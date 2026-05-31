<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesEnvParser;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddSanctumHasApiTokens;

use function Laravel\Prompts\select;

class Backups extends Command
{
    use InstallsPackages;
    use UsesEnvParser;
    use UsesPhpParser;

    protected $signature = 'cook:api {--force} {--skip-pint}';

    protected $description = 'Install API';

    protected string $driver;

    public string $publishGroup = 'api';

    protected array $packages = [];

    public function handle(): void
    {
        $this->driver = select('Which driver?', [
            'passport' => 'Passport',
            'sanctum' => 'Sanctum',
        ], 'passport');

        $this->setupDriver();

        $this->call('vendor:publish', [
            '--tag' => 'cook-api',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->runInstall();

        $this->addCode();
    }

    protected function setupDriver(): void
    {
        if ($this->driver === 'passport') {
            $this->packages['laravel/passport'] = 'require';
        }

        if ($this->driver === 'sanctum') {
            $this->packages['laravel/sanctum'] = 'require';
        }
    }

    protected function runInstall(): void
    {
        $this->components->info('Running installation');

        if ($this->driver === 'passport') {
            $this->runInNewProcess('php artisan install:api --passport --without-migration-prompt');
        }

        if ($this->driver === 'sanctum') {
            $this->runInNewProcess('php artisan install:api --without-migration-prompt');
        }
    }

    protected function addCode(): void
    {
        $this->components->info('Preparing User model');

        if ($this->driver === 'sanctum') {
            $this->applyPhpVisitors(app_path('Models/User.php'), [
                AddSanctumHasApiTokens::class,
            ]);
        }
    }
}
