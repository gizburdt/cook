<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;

class Filament extends Command
{
    use InstallsPackages;

    protected $signature = 'cook:filament {--force}';

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

        // todo: add developer login to PortalProvider

        $this->openDocs();
    }
}
