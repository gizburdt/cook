<?php

namespace Gizburdt\Cook\Commands;

use Gizburdt\Cook\Commands\Concerns\InstallsPackages;
use Gizburdt\Cook\Commands\Concerns\UsesPhpParser;
use Gizburdt\Cook\Commands\Concerns\UsesJavascriptParser;
use Gizburdt\Cook\Commands\NodeVisitors\AddAdminPanelProvider;

class FilamentPanel extends Command
{
    use InstallsPackages;
    use UsesJavascriptParser;
    use UsesPhpParser;

    protected $signature = 'cook:filament:panel {--force} {--skip-pint}';

    protected $description = 'Install Filament panel';

    protected array $packages = [
        'dutchcodingcompany/filament-developer-logins' => 'require',
        'filament/filament' => 'require',
    ];

    public function handle(): void
    {
        $this->call('vendor:publish', [
            '--tag' => 'cook-filament-panel',
            '--force' => $this->option('force'),
        ]);

        $this->tryInstallPackages();

        $this->addAdminPanelProvider();

        $this->installFilamentTheme();
    }

    protected function addAdminPanelProvider(): void
    {
        $this->applyPhpVisitors(base_path('bootstrap/providers.php'), [
            AddAdminPanelProvider::class,
        ]);
    }

    protected function installFilamentTheme(): void
    {
        $this->call('filament:theme', [
            'panel' => 'admin',
        ]);

        // Config
        $themePath = 'resources/css/filament/admin/theme.css';

        if ($this->addInputToViteConfig(base_path('vite.config.js'), $themePath)) {
            $this->components->info('Added Filament theme to vite.config.js');
        }

        // NPM
        $this->runInNewProcess('npm run build');
    }
}
