<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddAppAuthenticationMethods;
use Gizburdt\Cook\Commands\NodeVisitors\AddCanAccessPanel;
use Gizburdt\Cook\Commands\NodeVisitors\AddFilamentConfiguration;

class Filament extends Command
{
    use InstallsPackages;
    use UsesPhpParser;

    protected $signature = 'cook:filament {--force} {--skip-pint}';

    protected $description = 'Install Filament';

    protected string $docs = 'https://filamentphp.com/docs/4.x/introduction/installation';

    protected array $packages = [
        'dutchcodingcompany/filament-developer-logins' => 'require',
        'filament/filament' => 'require',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-filament',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->components->info('Updating composer.json');

        $this->composer->addScript('post-autoload-dump', '@php artisan filament:upgrade');

        $this->components->info('Adding configuration');

        $this->addConfiguration();

        $this->installFilament();

        $this->runPint();

        $this->openDocs();
    }

    protected function addConfiguration(): void
    {
        $this->applyPhpVisitors(app_path('Providers/AppServiceProvider.php'), [
            AddFilamentConfiguration::class,
        ]);

        $this->applyPhpVisitors(app_path('Models/User.php'), [
            AddCanAccessPanel::class,
            AddAppAuthenticationMethods::class,
        ]);
    }

    protected function installFilament(): void
    {
        $this->callInNewProcess('filament:install', [
            '--no-interaction',
        ]);
    }
}
