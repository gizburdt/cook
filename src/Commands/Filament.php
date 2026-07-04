<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\PromptsMfaMethods;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddCanAccessPanel;
use Gizburdt\Cook\Commands\NodeVisitors\AddFilamentConfiguration;
use Gizburdt\Cook\Commands\NodeVisitors\AddMfaAuthenticationMethods;
use Gizburdt\Cook\Enums\MfaMethod;

class Filament extends Command
{
    use InstallsPackages;
    use PromptsMfaMethods;
    use UsesPhpParser;

    protected $signature = 'cook:filament {--force} {--skip-pint}';

    protected $description = 'Install Filament';

    protected string $docs = 'https://filamentphp.com/docs/4.x/introduction/installation';

    public string $publishGroup = 'filament';

    public array $publishes = [
        'Filament' => 'app/Filament',
        'config/filament.php' => 'config/filament.php',
    ];

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

        $methods = $this->promptMfaMethods();

        $this->publishMigrations($methods);

        $visitors = [AddCanAccessPanel::class];

        if (! empty($methods)) {
            $visitors[] = AddMfaAuthenticationMethods::make($methods);
        }

        $this->applyPhpVisitors(app_path('Models/User.php'), $visitors);
    }

    /**
     * @param  array<int, MfaMethod>  $methods
     */
    protected function publishMigrations(array $methods): void
    {
        foreach ($methods as $method) {
            $source = __DIR__.'/../../publish/filament/database/migrations/'.$method->migration();

            $destination = database_path('migrations/'.$method->migration());

            if (! $this->option('force') && $this->files->exists($destination)) {
                continue;
            }

            $this->files->copy($source, $destination);
        }
    }

    protected function installFilament(): void
    {
        $this->callInNewProcess('filament:install', [
            '--no-interaction',
        ]);
    }
}
